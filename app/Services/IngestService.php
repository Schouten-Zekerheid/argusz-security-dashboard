<?php

namespace App\Services;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use App\Repositories\FindingStatusRepository;
use App\Repositories\PipelineRunRepository;
use App\Support\RepositoryUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class IngestService
{
    public function __construct(
        private readonly PipelineRunRepository $pipelineRunRepository,
        private readonly FindingStatusRepository $findingStatusRepository,
        private readonly JiraService $jiraService,
        private readonly NotificationService $notificationService,
    ) {}

    public function store(Service $service, array $payload): PipelineRun
    {
        $hash = hash('sha256', json_encode($payload));

        if ($this->pipelineRunRepository->existsByHash($hash)) {
            Log::warning('Duplicate ingest payload genegeerd', [
                'service' => $service->name,
                'hash' => $hash,
            ]);
            throw new ConflictHttpException('run already ingested');
        }

        $pipelineRun = $this->pipelineRunRepository->create($service, $hash, $payload);

        $totalFindings = collect($payload['runs'])
            ->sum(fn ($run): int => count($run['findings']));

        $incomingBranch = $payload['meta']['branch'] ?? null;
        $scanSource = $this->resolveScanSource($payload);
        $defaultBranch = $this->resolveDefaultBranch(
            $service,
            $payload['meta']['repository_url'] ?? null,
        );
        $isDefaultBranch = $incomingBranch !== null && $incomingBranch === $defaultBranch;

        [$currentFingerprints, $criticalToNotify] = $this->collectFingerprintsFromRuns(
            $payload['runs'],
            $service,
            $pipelineRun,
            $scanSource,
        );

        if ($isDefaultBranch) {
            $serviceId = (string) $service->_id;
            $pipelineRunId = (string) $pipelineRun->_id;

            $returningJiraKeys = $this->findingStatusRepository
                ->markReturning($serviceId, $scanSource, $pipelineRunId, $currentFingerprints);
            $resolvedJiraKeys = $this->findingStatusRepository
                ->markResolved($serviceId, $scanSource, $currentFingerprints);

            if ($this->jiraService->statusSyncEnabled()) {
                foreach ($resolvedJiraKeys as $issueKey) {
                    $this->jiraService->transitionToStatus($issueKey, (string) config('services.jira.status_done'));
                }

                foreach ($returningJiraKeys as $issueKey) {
                    $this->jiraService->transitionToStatus($issueKey, (string) config('services.jira.status_reopen'));
                }
            }
        }

        if ($this->notificationService->criticalFindingsEnabled()) {
            foreach ($criticalToNotify as $finding) {
                $findingStatus = $this->findingStatusRepository->findByUniqueKeys(
                    (string) $service->_id,
                    $scanSource,
                    $finding['fingerprint']
                );

                if ($findingStatus instanceof FindingStatus) {
                    $finding['_id'] = (string) $findingStatus->_id;
                    $finding['argusz_url'] = route('findings.show', ['id' => (string) $findingStatus->_id]);
                }

                $this->notificationService->notifyCriticalFinding($finding, $service->name);
            }
        }

        Log::info('Scan ingest succesvol verwerkt', [
            'service' => $service->name,
            'pipeline_run_id' => (string) $pipelineRun->_id,
            'commit_hash' => $payload['meta']['commit_hash'],
            'findings' => $totalFindings,
            'branch' => $incomingBranch,
            'is_default_branch' => $isDefaultBranch,
            'scan_source' => $scanSource,
        ]);

        return $pipelineRun;
    }

    /** @return array{0: array<string>, 1: array<array<string, mixed>>} */
    private function collectFingerprintsFromRuns(
        array $runs,
        Service $service,
        PipelineRun $pipelineRun,
        string $scanSource,
    ): array {
        $fingerprints = [];
        $criticalToNotify = [];

        foreach ($runs as $toolRun) {
            foreach ($toolRun['findings'] as $finding) {
                if (Severity::fromValue($finding['severity'] ?? null) === Severity::Critical) {
                    $currentStatus = $this->findingStatusRepository
                        ->getStatus((string) $service->_id, $scanSource, $finding['fingerprint']);

                    // Alleen notificeren als het een nieuwe bevinding is, of als deze vanuit een afgesloten status komt
                    if (! $currentStatus || in_array($currentStatus, ['resolved', 'closed'])) {
                        $criticalToNotify[] = array_merge($finding, [
                            'tool' => $toolRun['tool'],
                        ]);
                    }
                }

                $this->findingStatusRepository
                    ->upsert($service, $pipelineRun, $scanSource, $toolRun['tool'], $finding);

                $fingerprint = $finding['fingerprint'] ?? null;
                if ($fingerprint !== null && $fingerprint !== '') {
                    $fingerprints[] = $fingerprint;
                }
            }
        }

        return [$fingerprints, $criticalToNotify];
    }

    private function resolveScanSource(array $payload): string
    {
        return ($payload['meta']['tier'] ?? null) === 'container'
            ? 'container'
            : 'github';
    }

    /**
     * Returns the default branch for a service, fetching it from the configured
     * source control provider when possible.
     */
    private function resolveDefaultBranch(
        Service $service,
        ?string $repositoryUrl,
    ): ?string {
        if ($service->default_branch !== null) {
            return $service->default_branch;
        }

        $ownerRepo = RepositoryUrl::ownerRepo(
            $repositoryUrl ?? $service->repository_url,
        );

        if ($ownerRepo === null) {
            return null;
        }

        $apiUrl = config('integrations.scm.github.api_url');
        $token = config('integrations.scm.github.token');

        $response = Http::withToken($token)
            ->get("{$apiUrl}/repos/{$ownerRepo}");

        if (! $response->successful()) {
            Log::warning('Kon default branch niet ophalen via SCM API', [
                'repository' => $ownerRepo,
                'status' => $response->status(),
            ]);

            return null;
        }

        $defaultBranch = $response->json('default_branch');

        if ($defaultBranch !== null) {
            $service->default_branch = $defaultBranch;
            $service->save();
        }

        return $defaultBranch;
    }
}
