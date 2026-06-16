<?php

namespace App\Livewire;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use App\Repositories\FindingStatusRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Service $service
 * @property-read array $latestMeta
 * @property-read Collection $findings
 * @property-read Collection $pipelineRuns
 * @property-read Collection $toolSections
 * @property-read array $stats
 * @property-read array $severityBreakdown
 * @property-read array $scanHistory
 * @property-read PipelineRun|null $latestDefaultBranchRun
 */
#[Layout('components.layouts.app')]
class ServiceDetail extends Component
{
    public string $serviceId;

    #[Url]
    public ?string $type = null;

    private FindingStatusRepository $findingStatusRepository;

    public function boot(FindingStatusRepository $findingStatusRepository): void
    {
        $this->findingStatusRepository = $findingStatusRepository;
    }

    public function mount(string $id): void
    {
        $service = Service::find($id);
        abort_if($service === null, 404);
        $this->serviceId = (string) $service->_id;
    }

    #[Computed]
    public function service(): Service
    {
        return Service::find($this->serviceId);
    }

    #[Computed]
    public function latestDefaultBranchRun(): ?PipelineRun
    {
        $defaultBranch = $this->service->default_branch;

        if ($defaultBranch === null) {
            return null;
        }

        $query = PipelineRun::where('service_id', $this->serviceId)
            ->where('meta.branch', $defaultBranch);

        if ($this->type === 'azure') {
            $query->where('meta.tier', 'container');
        } else {
            $query->where('meta.tier', '!=', 'container');
        }

        return $query->get(['_id', 'meta', 'runs', 'ingested_at'])
            ->sortByDesc('ingested_at')
            ->first();
    }

    #[Computed]
    public function latestMeta(): array
    {
        $run = $this->latestDefaultBranchRun ?? $this->pipelineRuns->first();

        return $run !== null ? ($run->meta ?? []) : [];
    }

    #[Computed]
    public function findings(): Collection
    {
        // No default branch scan yet — nothing to show on the left side
        $defaultRun = $this->latestDefaultBranchRun;
        if ($defaultRun === null) {
            return collect();
        }

        $fingerprints = collect($defaultRun->runs ?? [])
            ->flatMap(fn (array $toolRun): array => $toolRun['findings'] ?? [])
            ->pluck('fingerprint')
            ->filter()
            ->values()
            ->all();

        return $this->findingStatusRepository
            ->findOpenDefaultRunFindings(
                $this->serviceId,
                $this->type === 'azure' ? 'container' : 'github',
                $fingerprints,
            )
            ->sortBy(fn (FindingStatus $findingStatus): int => Severity::fromValue($findingStatus->severity)->sortOrder())
            ->values()
            ->map(function (FindingStatus $finding): array {
                $severity = Severity::fromValue($finding->severity);

                return [
                    'id' => (string) $finding->_id,
                    'title' => $this->normalizeString(
                        $finding->title,
                        'Untitled finding',
                    ),
                    'tool_key' => is_array($finding->tool)
                        ? ($finding->tool['key'] ?? 'unknown')
                        : $this->normalizeString($finding->tool, 'unknown'),
                    'tool_category' => is_array($finding->tool)
                        ? ($finding->tool['category'] ?? '')
                        : '',
                    'severity' => $severity->value,
                    'status' => $finding->current_status,
                    'type' => $this->normalizeString($finding->type, 'unknown'),
                    'reference_id' => $this->normalizeNullableString(
                        $finding->reference_id,
                    ),
                    'status_updated_at' => $finding->status_updated_at,
                    'sev_text' => $severity->textClass(),
                    'sev_dot' => $severity->dotClass(),
                ];
            });
    }

    #[Computed]
    public function pipelineRuns(): Collection
    {
        $query = PipelineRun::where('service_id', $this->serviceId);

        if ($this->type === 'azure') {
            $query->where('meta.tier', 'container');
        } else {
            $query->where('meta.tier', '!=', 'container');
        }

        return $query->get(['_id', 'meta', 'runs', 'ingested_at'])
            ->sortByDesc('ingested_at')
            ->take(10)
            ->values();
    }

    /**
     * One section per tool from the latest default-branch pipeline run,
     * merged with open findings.
     */
    #[Computed]
    public function toolSections(): Collection
    {
        $findingsByCategory = $this->findings->groupBy('tool_category');
        $latestRun = $this->latestDefaultBranchRun;

        if ($latestRun === null) {
            return $findingsByCategory->map(
                fn (Collection $findings, string $category): array => $this->buildToolSection(
                    $category,
                    $findings->first()['tool_key'] ?? 'unknown',
                    'success',
                    $findings,
                ),
            )->values();
        }

        return collect($latestRun->runs ?? [])
            ->map(function ($run) use ($findingsByCategory): array {
                $category = data_get($run, 'tool.category', '');
                $toolKey = data_get($run, 'tool.key', 'unknown');
                $scanStatus = data_get($run, 'scan.status', 'unknown');
                $findings = $findingsByCategory->get($category, collect());

                return $this->buildToolSection(
                    $category,
                    $toolKey,
                    $scanStatus,
                    $findings,
                );
            });
    }

    #[Computed]
    public function stats(): array
    {
        $findings = $this->findings;
        $criticalCount = $findings->where('severity', Severity::Critical->value)->count();
        $openCount = $findings->count();
        $defaultRun = $this->latestDefaultBranchRun;
        $lastRunAt = $defaultRun->ingested_at
            ?? $this->pipelineRuns->first()?->ingested_at;
        $isStale = $lastRunAt === null
            || Carbon::parse($lastRunAt)->lt(now()->subDays(7));
        $noDefaultBranchScan = $defaultRun === null;

        return [
            'open_count' => $openCount,
            'critical_count' => $criticalCount,
            'high_count' => $findings->where('severity', Severity::High->value)->count(),
            'last_run_at' => $lastRunAt,
            'is_stale' => $isStale,
            'no_default_branch_scan' => $noDefaultBranchScan,
            'status_badge' => match (true) {
                $noDefaultBranchScan => [
                    'bg-slate-500/20 text-slate-300 ring-1 ring-slate-400/40', 'UNKNOWN',
                ],
                $criticalCount > 0 => [
                    'bg-red-500/20 text-red-300 ring-1 ring-red-400/50', 'CRITICAL',
                ],
                $openCount > 0 => [
                    'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/40', 'WARNING',
                ],
                $isStale => [
                    'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/40', 'STALE',
                ],
                default => [
                    'bg-green-500/20 text-green-300 ring-1 ring-green-400/40', 'HEALTHY',
                ],
            },
        ];
    }

    /**
     * Severity counts + bar percentages for the sidebar breakdown widget.
     */
    #[Computed]
    public function severityBreakdown(): array
    {
        $total = $this->findings->count();
        $severities = Severity::breakdownRows();

        return array_map(function (array $sev) use ($total): array {
            $count = $this->findings->where('severity', $sev['key'])->count();

            return array_merge($sev, [
                'count' => $count,
                'pct' => $total > 0 ? round($count / $total * 100) : 0,
            ]);
        }, $severities);
    }

    /**
     * Scan history grouped by branch, showing the latest 2 runs per branch.
     * Branches are ordered by most recent activity.
     * Each group includes has_more/more_count so the view can link to the full history.
     *
     * @return array<int, array{
     *     branch: string,
     *     runs: list<array<string, mixed>>,
     *     has_more: bool,
     *     more_count: int,
     * }>
     */
    #[Computed]
    public function scanHistory(): array
    {
        $allRuns = PipelineRun::where('service_id', $this->serviceId)
            ->get(['_id', 'meta', 'runs', 'ingested_at'])
            ->sortByDesc('ingested_at');

        return $allRuns
            ->groupBy(function (PipelineRun $run): string {
                $branch = $run->meta['branch'] ?? '';

                return $branch !== '' ? (string) $branch : 'unknown';
            })
            ->map(function (Collection $branchRuns, string $branch): array {
                $total = $branchRuns->count();
                $shown = $branchRuns->take(1);

                return [
                    'branch' => $branch,
                    'runs' => $shown->map(function (PipelineRun $run): array {
                        $meta = $run->meta ?? [];
                        $findingCount = collect($run->runs ?? [])
                            ->flatMap(
                                fn (array $toolRun): mixed => $toolRun['findings'] ?? []
                            )
                            ->count();

                        return [
                            'run_id' => (string) $run->_id,
                            'ingested_at' => $run->ingested_at
                                ? Carbon::parse($run->ingested_at)->format('d M, H:i')
                                : '—',
                            'ingested_at_diff' => $run->ingested_at
                                ? Carbon::parse($run->ingested_at)->diffForHumans()
                                : null,
                            'actor' => $meta['actor'] ?? null,
                            'commit_short' => isset($meta['commit_hash'])
                                ? substr((string) $meta['commit_hash'], 0, 7)
                                : null,
                            'commit_hash' => $meta['commit_hash'] ?? null,
                            'repository_url' => $meta['repository_url']
                                ?? $this->service->repository_url
                                ?? null,
                            'finding_count' => $findingCount,
                            'status_text' => $findingCount > 0
                                ? $findingCount.' issues'
                                : 'Clean',
                            'status_class' => $findingCount > 0
                                ? 'text-red-400'
                                : 'text-green-400',
                        ];
                    })->all(),
                    'has_more' => $total > 1,
                    'more_count' => max(0, $total - 1),
                ];
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.service-detail')
            ->title($this->service->name);
    }

    private function buildToolSection(
        string $category,
        string $toolKey,
        string $scanStatus,
        Collection $findings,
    ): array {
        $hasIssues = $findings->count() > 0;
        $notRan = $scanStatus === 'missing';
        $hasCritical = $hasIssues
            && $findings->where('severity', Severity::Critical->value)->count() > 0;

        return [
            'category' => $category,
            'tool_key' => $toolKey,
            'scan_status' => $scanStatus,
            'findings' => $findings,
            'has_issues' => $hasIssues,
            'not_ran' => $notRan,
            'finding_count' => $findings->count(),
            'tool_label' => match ($category) {
                'SCA' => 'Dependency Check',
                'SAST' => 'Static Code Analysis',
                'SECRETS' => 'Secret Detection',
                default => strtoupper($toolKey),
            },
            'tool_badge_class' => match ($category) {
                'SCA' => 'bg-sky-500/15 text-sky-300 ring-1 ring-sky-400/30',
                'SAST' => 'bg-violet-500/15 text-violet-300 ring-1 ring-violet-400/30',
                'SECRETS' => 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/30',
                default => 'bg-slate-700/60 text-slate-300',
            },
            'border_class' => match (true) {
                $hasCritical => 'border-red-500/40',
                $hasIssues => 'border-amber-500/30',
                $notRan => 'border-slate-700/50',
                default => 'border-slate-800',
            },
        ];
    }

    private function normalizeString(mixed $value, string $fallback): string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item): bool => ! is_null($item) && $item !== '')
                ->first();
        }

        if (is_null($value) || $value === '') {
            return $fallback;
        }

        return is_scalar($value) ? (string) $value : $fallback;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item): bool => ! is_null($item) && $item !== '')
                ->first();
        }

        if (is_null($value) || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
