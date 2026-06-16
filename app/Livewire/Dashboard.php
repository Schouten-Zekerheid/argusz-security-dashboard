<?php

namespace App\Livewire;

use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @phpstan-property-read Collection<int, array{
 *     id: string,
 *     name: string,
 *     repo_url: string,
 *     environment: mixed,
 *     status: string,
 *     critical_count: int,
 *     open_count: int,
 *     last_run_at: mixed,
 * }> $allServices
 * @phpstan-property-read Collection<int, array{
 *     id: string,
 *     name: string,
 *     repo_url: string,
 *     environment: mixed,
 *     status: string,
 *     critical_count: int,
 *     open_count: int,
 *     last_run_at: mixed,
 * }> $allServicesFiltered
 */
#[Layout('components.layouts.app')]
#[Title('Services')]
class Dashboard extends Component
{
    public string $activeTab = 'all';

    public string $scanTypeFilter = 'all';

    public function activateTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function allServices(): Collection
    {
        $services = Service::where('active', true)->get();

        if ($services->isEmpty()) {
            return collect();
        }

        $serviceIds = $services->pluck('_id')->map(fn ($id): string => (string) $id)->all();

        $allFindings = FindingStatus::whereIn('current_status', ['open', 'returning'])
            ->whereIn('service_id', $serviceIds)
            ->get(['service_id', 'severity', 'scan_source']);

        $allRuns = PipelineRun::whereIn('service_id', $serviceIds)
            ->get(['_id', 'service_id', 'ingested_at', 'meta']);

        $findingsByService = $allFindings->groupBy('service_id');
        $runsByService = $allRuns->groupBy('service_id');

        $entries = collect();

        foreach ($services as $service) {
            $id = (string) $service->_id;
            $serviceFindings = $findingsByService->get($id, collect());
            $serviceRuns = $runsByService->get($id, collect());

            // 1. GitHub Entry
            if ($service->repository_url) {
                $githubRuns = $serviceRuns->filter(fn ($run): bool => ($run->meta['tier'] ?? '') !== 'container');
                $latestGithubRun = $githubRuns->sortByDesc('ingested_at')->first();

                $githubFindings = $serviceFindings->filter(
                    fn (FindingStatus $finding): bool => ($finding->scan_source ?? 'github') === 'github'
                );

                $criticalCount = $githubFindings->where('severity', 'CRITICAL')->count();
                $openCount = $githubFindings->count();

                $status = match (true) {
                    $latestGithubRun === null => 'unknown',
                    $criticalCount > 0 => 'critical',
                    $openCount > 0 => 'warning',
                    default => 'healthy',
                };

                $entries->push([
                    'id' => $id,
                    'type' => 'github',
                    'name' => $service->name,
                    'repo_url' => $service->repository_url,
                    'environment' => data_get($latestGithubRun, 'meta.environment'),
                    'tier' => data_get($latestGithubRun, 'meta.tier'),
                    'image_ref' => null,
                    'status' => $status,
                    'critical_count' => $criticalCount,
                    'open_count' => $openCount,
                    'last_run_at' => $latestGithubRun?->ingested_at,
                    'last_run_id' => $latestGithubRun !== null ? (string) $latestGithubRun->_id : null,
                    'no_default_branch_scan' => $latestGithubRun === null,
                ]);
            }

            // 2. Azure/Container Entry
            if ($service->image_ref) {
                $azureRuns = $serviceRuns->filter(fn ($run): bool => ($run->meta['tier'] ?? '') === 'container');
                $latestAzureRun = $azureRuns->sortByDesc('ingested_at')->first();

                $azureFindings = $serviceFindings->filter(
                    fn (FindingStatus $finding): bool => ($finding->scan_source ?? 'github') === 'container'
                );

                $criticalCount = $azureFindings->where('severity', 'CRITICAL')->count();
                $openCount = $azureFindings->count();

                $status = match (true) {
                    $latestAzureRun === null => 'unknown',
                    $criticalCount > 0 => 'critical',
                    $openCount > 0 => 'warning',
                    default => 'healthy',
                };

                $entries->push([
                    'id' => $id,
                    'type' => 'azure',
                    'name' => $service->name,
                    'repo_url' => $service->repository_url,
                    'environment' => data_get($latestAzureRun, 'meta.environment'),
                    'tier' => data_get($latestAzureRun, 'meta.tier'),
                    'image_ref' => $service->image_ref,
                    'status' => $status,
                    'critical_count' => $criticalCount,
                    'open_count' => $openCount,
                    'last_run_at' => $latestAzureRun?->ingested_at,
                    'last_run_id' => $latestAzureRun !== null ? (string) $latestAzureRun->_id : null,
                    'no_default_branch_scan' => $latestAzureRun === null,
                ]);
            }
        }

        return $entries;
    }

    #[Computed]
    public function allServicesFiltered(): Collection
    {
        if ($this->scanTypeFilter === 'all') {
            return $this->allServices;
        }

        return $this->allServices
            ->where('type', $this->scanTypeFilter)
            ->values();
    }

    #[Computed]
    public function services(): Collection
    {
        return match ($this->activeTab) {
            'critical', 'warning', 'healthy' => $this->filterByStatus($this->activeTab),
            default => $this->allServicesFiltered,
        };
    }

    #[Computed]
    public function tabCounts(): array
    {
        $services = $this->allServicesFiltered;

        return [
            'all' => $services->count(),
            'critical' => $services->where('status', 'critical')->count(),
            'warning' => $services->where('status', 'warning')->count(),
            'healthy' => $services->where('status', 'healthy')->count(),
        ];
    }

    /**
     * Breakdown of service statuses for the distribution widget.
     */
    #[Computed]
    public function statusBreakdown(): array
    {
        $services = $this->allServicesFiltered;
        $total = $services->count();
        $statuses = [
            [
                'label' => 'Critical',
                'key' => 'critical',
                'color' => 'bg-severity-critical',
                'text' => 'text-severity-critical',
            ],
            [
                'label' => 'Warning',
                'key' => 'warning',
                'color' => 'bg-severity-medium',
                'text' => 'text-severity-medium',
            ],
            [
                'label' => 'Healthy',
                'key' => 'healthy',
                'color' => 'bg-healthy',
                'text' => 'text-healthy',
            ],
        ];

        return array_map(function (array $status) use ($services, $total): array {
            $count = $services->where('status', $status['key'])->count();

            return array_merge($status, [
                'count' => $count,
                'pct' => $total > 0 ? round($count / $total * 100) : 0,
            ]);
        }, $statuses);
    }

    /**
     * Top-level stats for the summary cards.
     *
     * @return array{total_projects: int, critical_issues: int, open_findings: int}
     */
    #[Computed]
    public function stats(): array
    {
        $services = $this->allServicesFiltered;
        $scanned = $services->where('no_default_branch_scan', false);

        return [
            'total_projects' => $services->count(),
            'critical_issues' => $scanned->sum('critical_count'),
            'open_findings' => $scanned->sum('open_count'),
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }

    private function filterByStatus(string $status): Collection
    {
        return $this->allServicesFiltered
            ->filter(fn (array $service): bool => $service['status'] === $status)
            ->values();
    }
}
