<?php

namespace App\Livewire;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use App\Support\FindingMapper;
use App\Support\RepositoryUrl;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read PipelineRun $run
 * @property-read Service $service
 * @property-read Collection $allFindings
 * @property-read ?PipelineRun $prevRunData
 * @property-read array $prevFingerprints
 * @property-read array $toolSections
 * @property-read array $fixedFindings
 * @property-read array $severityBreakdown
 * @property-read array $statCards
 * @property-read array $runMeta
 * @property-read array $findingStatusIds
 */
#[Layout('components.layouts.app')]
class PipelineRunDetail extends Component
{
    public string $serviceId;

    public string $runId;

    public function mount(string $serviceId, string $runId): void
    {
        $service = Service::find($serviceId);
        abort_if($service === null, 404);

        $run = PipelineRun::where('_id', $runId)
            ->where('service_id', $serviceId)
            ->first();
        abort_if($run === null, 404);

        $this->serviceId = $serviceId;
        $this->runId = $runId;
    }

    #[Computed]
    public function service(): Service
    {
        return Service::find($this->serviceId);
    }

    #[Computed]
    public function run(): PipelineRun
    {
        return PipelineRun::find($this->runId);
    }

    /**
     * All findings from this run across all tools, as a flat Collection.
     */
    #[Computed]
    public function allFindings(): Collection
    {
        return collect($this->run->runs ?? [])
            ->flatMap(fn (array $toolRun): array => $toolRun['findings'] ?? [])
            ->map(fn (mixed $finding): array => is_array($finding) ? $finding : []);
    }

    /**
     * The previous pipeline run for the same branch
     * (sorted in PHP bcs CosmosDB has no index on ingested_at).
     * Falls back to any branch if the current run has no branch metadata.
     */
    #[Computed]
    public function prevRunData(): ?PipelineRun
    {
        $branch = $this->run->meta['branch'] ?? null;

        $runs = PipelineRun::where('service_id', $this->serviceId)
            ->where('ingested_at', '<', $this->run->ingested_at)
            ->get(['_id', 'runs', 'ingested_at', 'meta'])
            ->sortByDesc('ingested_at');

        if ($branch !== null) {
            $runs = $runs->filter(
                fn (PipelineRun $run): bool => ($run->meta['branch'] ?? null) === $branch
            );
        }

        return $runs->first();
    }

    /**
     * Fingerprint lookup map from the previous run
     * — array_flip for O(1) isset() lookups.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function prevFingerprints(): array
    {
        if ($this->prevRunData === null) {
            return [];
        }

        return collect($this->prevRunData->runs ?? [])
            ->flatMap(fn (array $toolRun): array => $toolRun['findings'] ?? [])
            ->pluck('fingerprint')
            ->filter()
            ->flip()
            ->all();
    }

    /**
     * Fingerprint → FindingStatus._id map for this service, for quick linking.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function findingStatusIds(): array
    {
        return FindingStatus::where('service_id', $this->serviceId)
            ->get(['_id', 'fingerprint'])
            ->filter(fn ($fs): bool => filled($fs->fingerprint))
            ->pluck('_id', 'fingerprint')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }

    /**
     * One section per tool from this run's runs[] array.
     */
    #[Computed]
    public function toolSections(): array
    {
        $prevFingerprints = $this->prevFingerprints;
        $hasPrevRun = $this->prevRunData !== null;
        $findingStatusIds = $this->findingStatusIds;

        $meta = $this->run->meta ?? [];
        $commitHash = $meta['commit_hash'] ?? null;
        $repoUrl = rtrim(
            (string) ($meta['repository_url'] ?? $this->service->repository_url ?? ''),
            '/',
        );
        $isGitHub = RepositoryUrl::isSupported($repoUrl);

        return collect($this->run->runs ?? [])->map(
            function (array $runEntry) use (
                $prevFingerprints,
                $hasPrevRun,
                $commitHash,
                $repoUrl,
                $isGitHub,
                $findingStatusIds
            ): array {
                $category = data_get($runEntry, 'tool.category', '');
                $toolKey = data_get($runEntry, 'tool.key', 'unknown');
                $scanStatus = data_get($runEntry, 'scan.status', 'unknown');
                $scanType = data_get($runEntry, 'scan.type', '');
                $findings = collect($runEntry['findings'] ?? []);

                $hasIssues = $findings->isNotEmpty();
                $notRan = $scanStatus === 'missing';
                $hasCritical = $hasIssues && $findings->contains(
                    fn ($f): bool => Severity::fromValue($f['severity'] ?? null) === Severity::Critical
                );

                $severityCounts = [];
                foreach (Severity::knownCases() as $severity) {
                    $severityCounts[$severity->value] = $findings->filter(
                        fn ($f): bool => Severity::fromValue($f['severity'] ?? null) === $severity
                    )->count();
                }

                $mappedFindings = $findings
                    ->sortBy(
                        fn ($f): int => Severity::fromValue($f['severity'] ?? null)->sortOrder()
                    )
                    ->map(fn (array $f): array => FindingMapper::map(
                        $f,
                        $prevFingerprints,
                        $hasPrevRun,
                        $commitHash,
                        $repoUrl,
                        $isGitHub,
                        $findingStatusIds,
                    ))
                    ->values()
                    ->all();

                return [
                    'category' => $category,
                    'tool_key' => $toolKey,
                    'scan_status' => $scanStatus,
                    'scan_type' => $scanType,
                    'finding_count' => $findings->count(),
                    'has_issues' => $hasIssues,
                    'not_ran' => $notRan,
                    'severity_counts' => $severityCounts,
                    'mapped_findings' => $mappedFindings,
                    'tool_label' => match ($category) {
                        'SCA' => 'Dependency Check',
                        'SAST' => 'Static Code Analysis',
                        'SECRETS' => 'Secret Detection',
                        'IaC' => 'IaC Misconfiguration',
                        default => strtoupper($toolKey),
                    },
                    'tool_badge_class' => match ($category) {
                        'SCA' => 'bg-sky-500/15 text-sky-300 ring-1 ring-sky-400/30',
                        'SAST' => 'bg-violet-500/15 text-violet-300 ring-1 ring-violet-400/30',
                        'SECRETS' => 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/30',
                        'IaC' => 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-400/30',
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
        )->all();
    }

    /**
     * Findings that were present in the previous run but are gone in this run (resolved).
     */
    #[Computed]
    public function fixedFindings(): array
    {
        if ($this->prevRunData === null) {
            return [];
        }

        $currentFingerprints = $this->allFindings
            ->pluck('fingerprint')
            ->filter()
            ->flip()
            ->all();

        return collect($this->prevRunData->runs ?? [])
            ->flatMap(function (array $toolRun): array {
                $category = data_get($toolRun, 'tool.category', '');

                return collect($toolRun['findings'] ?? [])
                    ->map(fn (mixed $finding): array => array_merge(
                        is_array($finding) ? $finding : [],
                        ['_category' => $category],
                    ))
                    ->all();
            })
            ->filter(fn (array $finding): bool => ! isset(
                $currentFingerprints[$finding['fingerprint'] ?? '']
            ))
            ->map(function (array $finding): array {
                $severity = Severity::fromValue($finding['severity'] ?? null, Severity::Info);

                return [
                    'severity' => $severity->value,
                    'sev_text' => $severity->textClass(),
                    'sev_bg' => $severity->panelClass(),
                    'title' => $finding['title'] ?? $finding['reference_id'] ?? 'Unknown',
                    'reference_id' => $finding['reference_id'] ?? null,
                    'category' => $finding['_category'] ?? '',
                    'file_path' => $finding['details']['file_path'] ?? null,
                    'line_start' => $finding['details']['line_start'] ?? null,
                    'package_name' => $finding['details']['package_name'] ?? null,
                ];
            })
            ->sortBy(
                fn (array $finding): int => Severity::fromValue($finding['severity'])->sortOrder()
            )
            ->values()
            ->all();
    }

    /**
     * Severity counts + bar percentages for the breakdown stat card.
     */
    #[Computed]
    public function severityBreakdown(): array
    {
        $findings = $this->allFindings;
        $total = $findings->count();

        $severities = Severity::breakdownRows();

        return array_map(function (array $sev) use ($findings, $total): array {
            $sevKey = $sev['key'];
            $count = $findings->filter(
                fn (array $finding): bool => Severity::fromValue($finding['severity'] ?? null)->value === $sevKey
            )->count();

            return array_merge($sev, [
                'count' => $count,
                'pct' => $total > 0 ? round($count / $total * 100) : 0,
            ]);
        }, $severities);
    }

    /**
     * The stat cards at the top of the page.
     */
    #[Computed]
    public function statCards(): array
    {
        $meta = $this->run->meta ?? [];
        $findingCount = $this->allFindings->count();

        $delta = null;
        $newCount = 0;

        if ($this->prevRunData !== null) {
            $prevCount = collect($this->prevRunData->runs ?? [])
                ->flatMap(fn (array $toolRun): array => $toolRun['findings'] ?? [])
                ->count();
            $delta = $findingCount - $prevCount;

            $prevFingerprints = $this->prevFingerprints;
            $newCount = $this->allFindings
                ->filter(
                    fn (array $finding): bool => isset($finding['fingerprint'])
                        && ! isset($prevFingerprints[$finding['fingerprint']])
                )
                ->count();
        }

        $commitHash = $meta['commit_hash'] ?? null;
        $repoUrl = $meta['repository_url'] ?? $this->service->repository_url ?? null;

        return [
            'commit' => [
                'label' => 'Commit Hash',
                'value' => $commitHash ? substr((string) $commitHash, 0, 7) : '—',
                'commit_hash' => $commitHash,
                'repo_url' => $repoUrl,
            ],
            'findings' => [
                'label' => 'Findings',
                'value' => (string) $findingCount,
                'delta' => $delta,
                'new_count' => $newCount,
                'delta_class' => match (true) {
                    $delta === null => '',
                    $delta > 0 => 'text-red-400',
                    $delta < 0 => 'text-green-400',
                    default => 'text-slate-500',
                },
            ],
        ];
    }

    /**
     * Metadata for the sidebar info card.
     */
    #[Computed]
    public function runMeta(): array
    {
        $meta = $this->run->meta ?? [];
        $repoUrl = $meta['repository_url'] ?? $this->service->repository_url ?? null;

        $environment = $meta['environment'] ?? null;
        $prNumber = isset($meta['pr_number']) ? (int) $meta['pr_number'] : null;

        return [
            'service' => $this->service->name,
            'repository' => $meta['repository'] ?? null,
            'repo_url' => $repoUrl,
            'branch' => $meta['branch'] ?? null,
            'environment' => $environment,
            'pr_number' => $prNumber,
            'pr_url' => $repoUrl && $prNumber ? $repoUrl.'/pull/'.$prNumber : null,
            'tier' => $meta['tier'] ?? null,
            'actor' => $meta['actor'] ?? null,
            'timestamp' => isset($meta['timestamp'])
                ? Carbon::parse($meta['timestamp'])->format('d M Y, H:i')
                : null,
            'ingested_at' => $this->run->ingested_at
                ? Carbon::parse($this->run->ingested_at)->format('d M Y, H:i')
                : null,
        ];
    }

    public function render(): View
    {
        $shortId = substr($this->runId, -8);

        return view('livewire.pipeline-run-detail')
            ->title('Run #'.$shortId.' — '.$this->service->name);
    }
}
