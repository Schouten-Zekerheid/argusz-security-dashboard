<?php

namespace App\Services;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Returns all active services enriched with their latest default-branch run
     * status and finding counts.
     */
    public function getActiveServicesWithStatus(): Collection
    {
        $services = Service::where('active', true)->get();

        if ($services->isEmpty()) {
            return collect();
        }

        $serviceIds = $services->pluck('_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
        $defaultBranchByService = $this->buildDefaultBranchMap($services);
        $latestDefaultRunByService = $this->resolveLatestDefaultRunPerService(
            $serviceIds,
            $defaultBranchByService,
        );
        $fingerprintsByService = $this->collectFingerprintsByService(
            $latestDefaultRunByService,
        );
        $allOpenFindings = $this->collectOpenFindingsByService($serviceIds);

        return $services->map(
            fn (Service $service): array => $this->mapServiceToResult(
                $service,
                $allOpenFindings,
                $latestDefaultRunByService,
                $fingerprintsByService
            )
        );
    }

    private function buildDefaultBranchMap(Collection $services): Collection
    {
        return $services->mapWithKeys(
            fn (Service $service): array => [
                (string) $service->_id => $service->default_branch,
            ]
        );
    }

    private function collectFingerprintsByService(
        Collection $latestDefaultRunByService
    ): Collection {
        return $latestDefaultRunByService->map(
            fn (?PipelineRun $run): array => $run instanceof PipelineRun
                ? collect($run->runs ?? [])
                    ->flatMap(fn (array $toolRun): array => $toolRun['findings'] ?? [])
                    ->pluck('fingerprint')
                    ->filter()
                    ->values()
                    ->all()
                : []
        );
    }

    private function collectOpenFindingsByService(array $serviceIds): Collection
    {
        return FindingStatus::whereIn('current_status', ['open', 'returning'])
            ->whereIn('service_id', $serviceIds)
            ->where(function ($query): void {
                $query->where('scan_source', 'github')
                    ->orWhereNull('scan_source');
            })
            ->get(['service_id', 'severity', 'fingerprint'])
            ->groupBy('service_id');
    }

    private function mapServiceToResult(
        Service $service,
        Collection $allOpenFindings,
        Collection $latestDefaultRunByService,
        Collection $fingerprintsByService,
    ): array {
        $id = (string) $service->_id;
        $latestDefaultRun = $latestDefaultRunByService->get($id);
        $allowedFingerprints = $fingerprintsByService->get($id, []);

        $findings = $allOpenFindings->get($id, collect());

        if ($allowedFingerprints !== []) {
            $findings = $findings->filter(
                fn (FindingStatus $finding): bool => in_array(
                    $finding->fingerprint,
                    $allowedFingerprints,
                    true,
                )
            );
        }

        $criticalCount = $findings->where('severity', Severity::Critical->value)->count();
        $openCount = $findings->count();
        $noDefaultBranchScan = $latestDefaultRun === null;

        return [
            'id' => $id,
            'name' => $service->name,
            'repo_url' => $service->repository_url,
            'environment' => data_get($latestDefaultRun, 'meta.environment'),
            'status' => $this->resolveStatus(
                $noDefaultBranchScan,
                $criticalCount,
                $openCount,
            ),
            'critical_count' => $criticalCount,
            'open_count' => $openCount,
            'last_run_at' => $latestDefaultRun?->ingested_at,
            'last_run_id' => $latestDefaultRun !== null
                ? (string) $latestDefaultRun->_id
                : null,
            'no_default_branch_scan' => $noDefaultBranchScan,
        ];
    }

    private function resolveStatus(
        bool $noDefaultBranchScan,
        int $criticalCount,
        int $openCount,
    ): string {
        return match (true) {
            $noDefaultBranchScan => 'unknown',
            $criticalCount > 0 => 'critical',
            $openCount > 0 => 'warning',
            default => 'healthy',
        };
    }

    /**
     * @param  array<string>  $serviceIds
     * @return Collection<string, mixed>
     */
    private function resolveLatestDefaultRunPerService(
        array $serviceIds,
        Collection $defaultBranchByService,
    ): Collection {
        $allRuns = PipelineRun::whereIn('service_id', $serviceIds)
            ->get(['_id', 'service_id', 'ingested_at', 'meta', 'runs']);

        return $allRuns
            ->groupBy('service_id')
            ->map(function (
                Collection $runs,
                string $serviceId
            ) use ($defaultBranchByService): ?PipelineRun {
                $branch = $defaultBranchByService->get($serviceId);

                if ($branch === null) {
                    return null;
                }

                return $runs
                    ->filter(
                        fn (PipelineRun $run): bool => ($run->meta['branch'] ?? null) === $branch
                            && ($run->meta['tier'] ?? null) !== 'container'
                    )
                    ->sortByDesc('ingested_at')
                    ->first();
            });
    }
}
