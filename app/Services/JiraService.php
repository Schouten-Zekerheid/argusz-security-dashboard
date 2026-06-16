<?php

namespace App\Services;

use App\Enums\Severity;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraService
{
    private readonly string $baseUrl;

    private readonly string $username;

    private readonly string $token;

    private readonly string $projectKey;

    private ?array $cachedRequest = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.jira.url'), '/');
        $this->username = (string) config('services.jira.username');
        $this->token = (string) config('services.jira.token');
        $this->projectKey = (string) config('services.jira.project_key');
    }

    public function enabled(): bool
    {
        return config('integrations.issue_tracker.enabled') === true
            && config('integrations.issue_tracker.provider') === 'jira'
            && $this->baseUrl !== ''
            && $this->username !== ''
            && $this->token !== ''
            && $this->projectKey !== '';
    }

    public function statusSyncEnabled(): bool
    {
        return $this->enabled()
            && config('integrations.issue_tracker.sync_statuses') === true;
    }

    /**
     * Prepare a pending HTTP request with the correct authentication and base URL.
     *
     * @return array{0: PendingRequest, 1: string}
     */
    private function prepareRequest(): array
    {
        if ($this->cachedRequest !== null) {
            return $this->cachedRequest;
        }

        $isServiceAccount = str_ends_with(strtolower($this->username), '@serviceaccount.atlassian.com');

        if ($isServiceAccount) {
            try {
                $response = Http::timeout(5)->get("{$this->baseUrl}/_edge/tenant_info");
                if ($response->successful()) {
                    $cloudId = $response->json('cloudId');
                    if ($cloudId) {
                        return $this->cachedRequest = [
                            Http::withToken($this->token)->timeout(10),
                            "https://api.atlassian.com/ex/jira/{$cloudId}",
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to resolve Jira Cloud ID, falling back to Basic Auth', ['error' => $e->getMessage()]);
            }
        }

        return $this->cachedRequest = [
            Http::withBasicAuth($this->username, $this->token)->timeout(10),
            $this->baseUrl,
        ];
    }

    /**
     * Create a Jira issue for a security finding.
     *
     * @param  array<string, mixed>  $finding  Normalized finding data from FindingStatus
     * @return string|null The created issue key (e.g. "SEC-42"), or null on failure
     */
    public function createIssueForFinding(array $finding): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $summary = '['.config('app.name').'] '.($finding['title'] ?? $finding['reference_id'] ?? 'Onbekende bevinding');

        $description = $this->buildDescription($finding);

        $payload = [
            'fields' => [
                'project' => ['key' => $this->projectKey],
                'summary' => $summary,
                'description' => $description,
                'issuetype' => ['name' => 'Bug'],
                'priority' => ['name' => $this->mapSeverityToPriority($finding['severity'] ?? '')],
            ],
        ];

        [$request, $url] = $this->prepareRequest();

        try {
            $response = $request->post("{$url}/rest/api/2/issue", $payload);
        } catch (ConnectionException $e) {
            Log::error('Jira connection failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            session()->flash('jira_error', 'Connection error: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            $responseBody = $response->body();
            $errorDetails = $response->json();

            $errorMessage = $responseBody;
            if (is_array($errorDetails)) {
                if (! empty($errorDetails['errorMessages'])) {
                    $errorMessage = implode(', ', $errorDetails['errorMessages']);
                } elseif (! empty($errorDetails['errors'])) {
                    $errorMessage = implode(', ', array_map(
                        fn ($k, $v): string => is_array($v) ? "$k: ".json_encode($v) : "$k: $v",
                        array_keys($errorDetails['errors']),
                        $errorDetails['errors']
                    ));
                }
            }

            Log::error('Jira API error', [
                'status' => $response->status(),
                'payload' => $payload,
                'body' => $responseBody,
                'parsed_error' => $errorMessage,
                'url' => "{$url}/rest/api/2/issue",
                'username' => $this->username,
                'project_key' => $this->projectKey,
            ]);

            session()->flash('jira_error', "Status {$response->status()}: {$errorMessage}");

            return null;
        }

        return $response->json('key');
    }

    public function browseUrl(string $issueKey): string
    {
        return "{$this->baseUrl}/browse/{$issueKey}";
    }

    /**
     * Check whether a Jira issue still exists.
     *
     * @return bool|null true = exists, false = deleted (404), null = check failed (network/auth error)
     */
    public function issueExists(string $issueKey): ?bool
    {
        [$request, $url] = $this->prepareRequest();

        try {
            $response = $request->get("{$url}/rest/api/2/issue/{$issueKey}", [
                'fields' => 'summary',
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Jira connection failed checking issue existence', [
                'issue' => $issueKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->status() === 404) {
            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::warning('Jira issue existence check returned unexpected status', [
            'issue' => $issueKey,
            'status' => $response->status(),
        ]);

        return null;
    }

    /**
     * Transition a Jira issue to the target status by name (e.g. "Done", "To Do").
     * Returns true on success, false if the transition was not found or the request failed.
     */
    public function transitionToStatus(string $issueKey, string $targetStatusName): bool
    {
        if (! $this->statusSyncEnabled()) {
            return false;
        }

        [$request, $url] = $this->prepareRequest();

        try {
            $response = $request->get("{$url}/rest/api/2/issue/{$issueKey}/transitions");
        } catch (ConnectionException $e) {
            Log::error('Jira connection failed fetching transitions', ['error' => $e->getMessage(), 'issue' => $issueKey]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('Jira transitions fetch failed', ['status' => $response->status(), 'issue' => $issueKey]);

            return false;
        }

        $transitions = $response->json('transitions') ?? [];
        $transitionId = null;

        foreach ($transitions as $transition) {
            if (strcasecmp($transition['to']['name'] ?? '', $targetStatusName) === 0) {
                $transitionId = $transition['id'];
                break;
            }
        }

        if ($transitionId === null) {
            Log::warning('Jira transition not found', ['issue' => $issueKey, 'target_status' => $targetStatusName]);

            return false;
        }

        try {
            $response = $request->post("{$url}/rest/api/2/issue/{$issueKey}/transitions", [
                'transition' => ['id' => $transitionId],
            ]);
        } catch (ConnectionException $e) {
            Log::error('Jira connection failed applying transition', ['error' => $e->getMessage(), 'issue' => $issueKey]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('Jira transition failed', ['status' => $response->status(), 'issue' => $issueKey, 'body' => $response->body()]);

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $finding
     */
    private function buildDescription(array $finding): string
    {
        $lines = [];

        $lines[] = 'h2. Bevindingsdetails';
        $lines[] = '';
        $lines[] = '*Severity:* '.strtoupper($finding['severity'] ?? 'UNKNOWN');
        $lines[] = '*Tool:* '.(is_array($finding['tool']) ? ($finding['tool']['key'] ?? 'onbekend') : ($finding['tool'] ?? 'onbekend'));
        $lines[] = '*Type:* '.($finding['type'] ?? '—');

        if (($finding['reference_id'] ?? null) !== null) {
            $lines[] = '*Referentie:* '.$finding['reference_id'];
        }

        if (($finding['fingerprint'] ?? null) !== null) {
            $lines[] = '*Fingerprint:* '.$finding['fingerprint'];
        }

        if (($finding['description'] ?? null) !== null) {
            $lines[] = '';
            $lines[] = 'h2. Omschrijving';
            $lines[] = '';
            $lines[] = $finding['description'];
        }

        if (($finding['argusz_url'] ?? null) !== null) {
            $lines[] = '';
            $lines[] = 'h2. Link';
            $lines[] = '';
            $lines[] = '[Bekijk bevinding in '.config('app.name').'|'.$finding['argusz_url'].']';
        }

        return implode("\n", $lines);
    }

    private function mapSeverityToPriority(string $severity): string
    {
        return Severity::fromValue($severity)->jiraPriority();
    }
}
