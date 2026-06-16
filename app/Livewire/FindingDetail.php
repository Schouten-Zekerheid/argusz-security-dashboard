<?php

namespace App\Livewire;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use App\Models\User;
use App\Repositories\FindingStatusRepository;
use App\Services\JiraService;
use App\Support\RepositoryUrl;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read FindingStatus $findingStatus
 * @property-read string $severityRaw
 * @property-read string $statusRaw
 * @property-read string $tool
 * @property-read Service|null $service
 * @property-read list<array{label: string, value: string}> $findingDetailRows
 * @property-read list<array{label: string, value: string|null}> $packageInfoRows
 * @property-read array<string, mixed> $findingDetails
 * @property-read string|null $sourceUrl
 * @property-read string|null $githubUrl
 * @property-read array{language: string, rawLines: list<string>, firstLineNumber: int, highlightedNumbers: list<int>}|null $snippetViewData
 * @property-read string $severityBadge
 * @property-read string $statusBadge
 * @property-read bool $isSlaBreached
 * @property-read int|null $slaThresholdDays
 * @property-read int|null $slaDaysRemaining
 * @property-read bool $findingCanBeSnoozed
 * @property-read bool $findingCanBeUnsnoozed
 * @property-read array<int, array<string, mixed>> $history
 *
 * @phpstan-property-read array{
 *     lines: array<int, array{number: int, content: string, highlighted: bool}>,
 *     language: string,
 * }|null $codeSnippet
 */
#[Layout('components.layouts.app')]
class FindingDetail extends Component
{
    public string $findingId;

    public string $activeTab = 'details';

    // --- Snooze modal state ---
    public bool $showSnoozeModal = false;

    public string $snoozeReason = '';

    // --- Unsnooze modal state ---
    public bool $showUnsnoozeModal = false;

    protected JiraService $jira;

    public function boot(JiraService $jira): void
    {
        $this->jira = $jira;
    }

    public function mount(string $id): void
    {
        $finding = FindingStatus::find($id);
        abort_if($finding === null, 404);

        $this->findingId = $id;

        $this->verifyJiraTicket($finding);
    }

    /**
     * Check if the linked Jira ticket still exists.
     * If Jira confirms it was deleted (404), clear the key.
     * On network/auth errors we leave the link intact (no false positives).
     */
    private function verifyJiraTicket(FindingStatus $finding): void
    {
        if ($finding->jira_issue_key === null) {
            return;
        }

        $exists = $this->jira->issueExists($finding->jira_issue_key);

        if ($exists === false) {
            $finding->jira_issue_key = null;
            $finding->save();
        }
    }

    #[Computed]
    public function findingStatus(): FindingStatus
    {
        return FindingStatus::findOrFail($this->findingId);
    }

    #[Computed]
    public function service(): ?Service
    {
        $serviceId = (string) $this->findingStatus->service_id;

        return Service::find($serviceId);
    }

    /**
     * The full finding details from the pipeline run, matched by fingerprint.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function findingDetails(): array
    {
        $pipelineRunId = (string) $this->findingStatus->pipeline_run_id;
        $fingerprint = $this->findingStatus->fingerprint;

        $run = PipelineRun::find($pipelineRunId);

        if ($run === null) {
            return [];
        }

        foreach ($run->runs ?? [] as $toolRun) {
            foreach ($toolRun['findings'] ?? [] as $f) {
                if (($f['fingerprint'] ?? null) === $fingerprint) {
                    return $f;
                }
            }
        }

        return [];
    }

    /**
     * GitHub URL to the exact file/line for this finding, or null if unavailable.
     */
    #[Computed]
    public function sourceUrl(): ?string
    {
        $details = $this->findingDetails['details'] ?? [];
        $filePath = $details['file_path'] ?? null;
        $lineStart = $details['line_start'] ?? null;

        if (! $filePath) {
            return null;
        }

        $run = PipelineRun::find((string) $this->findingStatus->pipeline_run_id);
        if ($run === null) {
            return null;
        }

        $meta = $run->meta ?? [];
        $repoUrl = rtrim(
            (string) ($meta['repository_url'] ?? $this->service->repository_url ?? ''),
            '/',
        );
        $commitHash = $meta['commit_hash'] ?? $details['commit'] ?? null;

        if (! $repoUrl || ! $commitHash) {
            return null;
        }

        return RepositoryUrl::fileUrl($repoUrl, (string) $commitHash, (string) $filePath, $lineStart);
    }

    #[Computed]
    public function githubUrl(): ?string
    {
        return $this->sourceUrl;
    }

    /**
     * Fetch a code snippet from GitHub around the finding's file/line.
     * Returns null if no file path, no commit, or the API call fails.
     *
     * @return array{
     *     lines: array<int, array{number: int, content: string, highlighted: bool}>,
     *     language: string,
     * }|null
     */
    #[Computed]
    public function codeSnippet(): ?array
    {
        $details = $this->findingDetails['details'] ?? [];
        $filePath = $details['file_path'] ?? null;
        $lineStart = isset($details['line_start']) ? (int) $details['line_start'] : null;
        $lineEnd = isset($details['line_end']) ? (int) $details['line_end'] : $lineStart;

        if (! $filePath || ! $lineStart) {
            return null;
        }

        $run = PipelineRun::find((string) $this->findingStatus->pipeline_run_id);
        if ($run === null) {
            return null;
        }

        $meta = $run->meta ?? [];
        $repoUrl = rtrim(
            (string) ($meta['repository_url'] ?? $this->service->repository_url ?? ''),
            '/',
        );
        $commitHash = $meta['commit_hash'] ?? $details['commit'] ?? null;

        if (! $repoUrl || ! $commitHash || ! RepositoryUrl::isSupported($repoUrl)) {
            return null;
        }

        $ownerRepo = RepositoryUrl::ownerRepo($repoUrl);
        if ($ownerRepo === null) {
            return null;
        }

        $apiUrl = config('integrations.scm.github.api_url');
        $token = config('integrations.scm.github.token');

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->get(
                    "{$apiUrl}/repos/{$ownerRepo}/contents/"
                    .ltrim((string) $filePath, '/'),
                    ['ref' => $commitHash],
                );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $content = base64_decode(str_replace("\n", '', $response->json('content') ?? ''));
        if ($content === '' || $content === '0') {
            return null;
        }

        $allLines = explode("\n", $content);
        $context = 4;
        $from = max(1, $lineStart - $context);
        $to = min(count($allLines), ($lineEnd ?? $lineStart) + $context);

        $lines = [];
        for ($i = $from; $i <= $to; $i++) {
            $lines[] = [
                'number' => $i,
                'content' => $allLines[$i - 1] ?? '',
                'highlighted' => $i >= $lineStart && $i <= ($lineEnd ?? $lineStart),
            ];
        }

        $ext = strtolower(pathinfo((string) $filePath, PATHINFO_EXTENSION));
        $basename = strtolower(basename((string) $filePath));
        $language = match (true) {
            in_array($basename, ['dockerfile', '.dockerfile']) => 'dockerfile',
            in_array($basename, ['.env', '.env.example', '.env.local']) => 'bash',
            $ext === 'php' => 'php',
            in_array($ext, ['js', 'mjs', 'cjs']) => 'javascript',
            in_array($ext, ['ts', 'tsx']) => 'typescript',
            $ext === 'py' => 'python',
            $ext === 'go' => 'go',
            $ext === 'java' => 'java',
            $ext === 'rb' => 'ruby',
            in_array($ext, ['yaml', 'yml']) => 'yaml',
            $ext === 'json' => 'json',
            in_array($ext, ['sh', 'bash']) => 'bash',
            in_array($ext, ['tf', 'hcl']) => 'hcl',
            in_array($ext, ['xml', 'html', 'htm', 'svg', 'blade']) => 'xml',
            $ext === 'vue' => 'vue',
            default => 'plaintext',
        };

        return ['lines' => $lines, 'language' => $language];
    }

    /**
     * Derived view data extracted from the code snippet for use in the template.
     *
     * @return array{
     *     language: string,
     *     rawLines: list<string>,
     *     firstLineNumber: int,
     *     highlightedNumbers: list<int>,
     * }|null
     */
    #[Computed]
    public function snippetViewData(): ?array
    {
        $snippet = $this->codeSnippet;

        if ($snippet === null) {
            return null;
        }

        return [
            'language' => $snippet['language'],
            'rawLines' => array_map(
                fn (array $l): string => $l['content'],
                $snippet['lines'],
            ),
            'firstLineNumber' => $snippet['lines'][0]['number'],
            'highlightedNumbers' => array_values(
                array_map(
                    fn (array $l): int => $l['number'],
                    array_filter($snippet['lines'], fn (array $l) => $l['highlighted']),
                ),
            ),
        ];
    }

    /**
     * Flat list of label/value pairs for the finding details panel.
     *
     * @return list<array{label: string, value: string}>
     */
    #[Computed]
    public function findingDetailRows(): array
    {
        return [
            ['label' => 'Service', 'value' => $this->service !== null
                ? $this->service->name
                : 'Unknown',
            ],
            ['label' => 'Tool', 'value' => $this->tool],
            ['label' => 'Type', 'value' => $this->findingStatus->type ?? '—'],
            [
                'label' => 'Reference',
                'value' => $this->findingStatus->reference_id ?? '—',
            ],
            [
                'label' => 'Fingerprint',
                'value' => $this->findingStatus->fingerprint ?? '—',
            ],
            ['label' => 'First Seen', 'value' => $this->findingStatus->created_at
                ? Carbon::parse($this->findingStatus->created_at)->format('d M Y, H:i').' UTC'
                : '—',
            ],
            [
                'label' => 'Updated by',
                'value' => $this->findingStatus->updated_by ?? 'system',
            ],
        ];
    }

    /**
     * Flat list of label/value pairs for the package info panel (SCA only).
     *
     * @return list<array{label: string, value: string|null}>
     */
    #[Computed]
    public function packageInfoRows(): array
    {
        $details = $this->findingDetails['details'] ?? [];

        return [
            ['label' => 'Package', 'value' => $details['package_name'] ?? null],
            [
                'label' => 'Installed version',
                'value' => $details['installed_version'] ?? null,
            ],
            ['label' => 'Fixed version', 'value' => $details['fixed_version'] ?? null],
        ];
    }

    #[Computed]
    public function statusRaw(): string
    {
        return strtoupper($this->findingStatus->current_status ?? 'UNKNOWN');
    }

    #[Computed]
    public function severityRaw(): string
    {
        return Severity::fromValue($this->findingStatus->severity)->value;
    }

    #[Computed]
    public function severityBadge(): string
    {
        return Severity::fromValue($this->severityRaw)->badgeClass();
    }

    #[Computed]
    public function statusBadge(): string
    {
        return match ($this->statusRaw) {
            'OPEN' => 'bg-red-500/20 text-red-200 ring-1 ring-red-400/60',
            'RETURNING' => 'bg-orange-500/20 text-orange-200 ring-1 ring-orange-400/60',
            'SNOOZED' => 'bg-violet-500/20 text-violet-200 ring-1 ring-violet-400/60',
            'RESOLVED',
            'CLOSED' => 'bg-green-500/20 text-green-200 ring-1 ring-green-400/60',
            default => 'bg-slate-700/40 text-slate-200 ring-1 ring-slate-500/60',
        };
    }

    #[Computed]
    public function isSlaBreached(): bool
    {
        $deadline = $this->slaDeadline();

        return $deadline instanceof CarbonInterface && Carbon::now()->greaterThan($deadline);
    }

    /**
     * The moment the SLA is breached: the creation date plus the threshold.
     * Recomputed on every request, so it always reflects the current SLA config.
     */
    private function slaDeadline(): ?CarbonInterface
    {
        $finding = $this->findingStatus;

        if ($finding->current_status !== 'open') {
            return null;
        }

        $days = $this->slaThresholdDays;
        if ($days === null || $finding->created_at === null) {
            return null;
        }

        return Carbon::parse($finding->created_at)->addDays($days);
    }

    #[Computed]
    public function slaThresholdDays(): ?int
    {
        return match (Severity::fromValue($this->severityRaw)) {
            Severity::Critical => config('sla.critical'),
            Severity::High => config('sla.high'),
            Severity::Medium => config('sla.medium'),
            Severity::Low => config('sla.low'),
            default => null,
        };
    }

    #[Computed]
    public function slaDaysRemaining(): ?int
    {
        $deadline = $this->slaDeadline();
        if (! $deadline instanceof CarbonInterface) {
            return null;
        }

        return (int) ceil(Carbon::now()->diffInDays($deadline, false));
    }

    #[Computed]
    public function tool(): string
    {
        $tool = $this->findingStatus->tool;

        return is_array($tool)
            ? (string) ($tool['key'] ?? collect($tool)->filter()->first() ?? 'unknown')
            : (string) $tool;
    }

    #[Computed]
    public function findingCanBeSnoozed(): bool
    {
        return in_array(
            strtolower($this->findingStatus->current_status ?? ''),
            ['open', 'returning'],
            true,
        );
    }

    #[Computed]
    public function findingCanBeUnsnoozed(): bool
    {
        return strtolower($this->findingStatus->current_status ?? '') === 'snoozed';
    }

    #[Computed]
    public function issueTrackerEnabled(): bool
    {
        return $this->jira->enabled();
    }

    #[Computed]
    public function issueTrackerBrowseUrl(): ?string
    {
        $issueKey = $this->findingStatus->jira_issue_key;

        return $issueKey !== null && $this->jira->enabled()
            ? $this->jira->browseUrl($issueKey)
            : null;
    }

    /**
     * History entries formatted for display, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function history(): array
    {
        $raw = $this->findingStatus->history;

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        return array_map(static fn (array $entry): array => [
            'from' => strtoupper($entry['from'] ?? '—'),
            'to' => strtoupper($entry['to'] ?? '—'),
            'at' => isset($entry['at'])
                ? Carbon::parse($entry['at'])->format('d M Y, H:i')
                : '—',
            'by' => $entry['by'] ?? '—',
            'reason' => $entry['reason'] ?? null,
        ], array_reverse($raw));
    }

    public function createJiraTicket(): void
    {
        if (! $this->jira->enabled()) {
            session()->flash('flash.error', 'Issue tracker is not enabled.');

            return;
        }

        $finding = $this->findingStatus;

        if ($finding->jira_issue_key !== null) {
            return;
        }

        $issueKey = $this->jira->createIssueForFinding([
            'title' => $finding->title,
            'reference_id' => $finding->reference_id,
            'severity' => $finding->severity,
            'tool' => $finding->tool,
            'type' => $finding->type,
            'fingerprint' => $finding->fingerprint,
            'description' => $this->findingDetails['description'] ?? null,
            'argusz_url' => route('findings.show', ['id' => $this->findingId]),
        ]);

        if ($issueKey === null) {
            session()->flash('flash.error', 'Failed to create issue. Check your configuration.');

            return;
        }

        $finding->jira_issue_key = $issueKey;
        $finding->save();

        /** @var User $user */
        $user = auth()->user();

        activity()
            ->useLog('findings')
            ->causedBy($user)
            ->event('issue_tracker_ticket_created')
            ->withProperties([
                'finding_id' => $this->findingId,
                'issue_key' => $issueKey,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Issue {$issueKey} created by {$user->email}");

        session()->flash('flash.success', "Issue {$issueKey} created successfully.");
        unset($this->findingStatus);
    }

    public function openSnoozeModal(): void
    {
        $this->authorize('findings.snooze');
        $this->showSnoozeModal = true;
    }

    public function closeSnoozeModal(): void
    {
        $this->showSnoozeModal = false;
        $this->snoozeReason = '';
    }

    public function confirmSnooze(FindingStatusRepository $repository): void
    {
        $this->authorize('findings.snooze');

        $this->validate([
            'snoozeReason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'snoozeReason.required' => 'Please provide a reason for snoozing.',
            'snoozeReason.min' => 'The reason must be at least 3 characters.',
            'snoozeReason.max' => 'The reason may not exceed 500 characters.',
        ]);

        /** @var User $user */
        $user = auth()->user();

        $snoozed = $repository->markSnoozed($this->findingStatus, $user->email, $this->snoozeReason ?: null);

        if (! $snoozed) {
            session()->flash(
                'flash.error',
                'Finding cannot be snoozed (status has already changed).',
            );
            $this->closeSnoozeModal();

            return;
        }

        activity()
            ->useLog('findings')
            ->causedBy($user)
            ->event('finding_snoozed')
            ->withProperties([
                'finding_id' => $this->findingId,
                'snooze_reason' => $this->snoozeReason,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Finding snoozed by {$user->email}");

        session()->flash('flash.success', 'Finding snoozed.');
        $this->closeSnoozeModal();
        unset($this->findingStatus);
    }

    public function openUnsnoozeModal(): void
    {
        $this->authorize('findings.snooze');
        $this->showUnsnoozeModal = true;
    }

    public function closeUnsnoozeModal(): void
    {
        $this->showUnsnoozeModal = false;
    }

    public function unsnooze(FindingStatusRepository $repository): void
    {
        $this->authorize('findings.snooze');

        /** @var User $user */
        $user = auth()->user();

        $unsnoozed = $repository->markUnsnoozed($this->findingStatus, $user->email);

        $this->closeUnsnoozeModal();

        if (! $unsnoozed) {
            session()->flash(
                'flash.error',
                'Finding could not be unsnoozed (status has already changed).',
            );

            return;
        }

        activity()
            ->useLog('findings')
            ->causedBy($user)
            ->event('finding_unsnoozed')
            ->withProperties([
                'finding_id' => $this->findingId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Finding unsnoozed by {$user->email}");

        session()->flash('flash.success', 'Finding marked as open again.');
        unset($this->findingStatus);
    }

    public function render(): View
    {
        return view('livewire.finding-detail')
            ->title('Finding Detail');
    }
}
