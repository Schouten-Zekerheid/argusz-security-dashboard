<?php

namespace App\Livewire;

use App\Models\Service;
use App\Repositories\PipelineRunRepository;
use App\Services\PdfExportService;
use App\Services\TrendAnalysisService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read int $overallSecurityScore
 * @property-read array $kpiSummary
 * @property-read array $riskScoreByRepository
 * @property-read array $findingsVelocity
 * @property-read array $slaAging
 * @property-read array $monthlyOpenFindings
 * @property-read array $criticalFindingsTrend
 * @property-read array $findingsByTool
 * @property-read array $findingsByPurpose
 * @property-read array $mttrByMonth
 * @property-read array $mttrWindowOptions
 * @property-read array $newFindingsByPeriod
 * @property-read array $scanTypeOptions
 * @property-read string $scanTypeLabel
 * @property-read array $scanFreshness
 * @property-read array $openFindingsGroupingOptions
 * @property-read array $openFindingsWindowOptions
 * @property-read array $criticalFindingsWindowOptions
 * @property-read array $newFindingsGroupingOptions
 * @property-read array $trendGranularityOptions
 * @property-read string $trendComparisonLabel
 * @property-read array $velocityGroupingOptions
 * @property-read array{subtitle: string, point_radius: int, hover_point_radius: int, x_max_ticks_limit: int|null} $velocityChartUi
 * @property-read Collection $services
 * @property-read ?array $metricsA
 * @property-read ?array $metricsB
 */
#[Layout('components.layouts.app')]
#[Title('Trend Analysis')]
class TrendAnalysis extends Component
{
    public bool $loading = true;

    public string $scanTypeFilter = 'all';

    public string $newFindingsGranularity = 'month';

    public string $openFindingsGranularity = 'month';

    public int $openFindingsMonthsBack = 6;

    public int $criticalFindingsMonthsBack = 6;

    public string $trendGranularity = 'month';

    public string $velocityGranularity = 'month';

    public string $mttrMonthsBack = '6';

    public ?string $compareRepoA = null;

    public ?string $compareRepoB = null;

    public function loadData(): void
    {
        $this->loading = false;
    }

    #[Computed]
    public function overallSecurityScore(): int
    {
        return app(TrendAnalysisService::class)->getOverallSecurityScore($this->scanTypeFilter);
    }

    #[Computed]
    public function kpiSummary(): array
    {
        // Use the dedicated trend granularity, independent of the velocity chart.
        $trendVelocity = app(TrendAnalysisService::class)->getFindingsVelocity(
            $this->trendGranularity,
            $this->scanTypeFilter,
        );

        return app(TrendAnalysisService::class)
            ->getKpiSummary($this->mttrByMonth(), $trendVelocity, $this->scanTypeFilter);
    }

    #[Computed]
    public function riskScoreByRepository(): array
    {
        return app(TrendAnalysisService::class)->getRiskScoreByRepository($this->scanTypeFilter);
    }

    #[Computed]
    public function findingsVelocity(): array
    {
        return app(TrendAnalysisService::class)->getFindingsVelocity(
            $this->velocityGranularity,
            $this->scanTypeFilter,
        );
    }

    #[Computed]
    public function slaAging(): array
    {
        return app(TrendAnalysisService::class)->getSlaAging($this->scanTypeFilter);
    }

    #[Computed]
    public function monthlyOpenFindings(): array
    {
        return app(TrendAnalysisService::class)->getMonthlyOpenFindings(
            monthsBack: $this->openFindingsMonthsBack,
            granularity: $this->openFindingsGranularity,
            scanType: $this->scanTypeFilter,
        );
    }

    #[Computed]
    public function criticalFindingsTrend(): array
    {
        return app(TrendAnalysisService::class)->getCriticalFindingsTrendPerRepository(
            $this->criticalFindingsMonthsBack,
            $this->scanTypeFilter,
        );
    }

    #[Computed]
    public function findingsByTool(): array
    {
        return app(TrendAnalysisService::class)->getFindingsByTool($this->scanTypeFilter);
    }

    #[Computed]
    public function findingsByPurpose(): array
    {
        return collect($this->findingsByTool())->map(fn ($item): array => array_merge($item, [
            'purpose' => app(TrendAnalysisService::class)->mapToolToPurpose($item['tool']),
        ]))->values()->all();
    }

    #[Computed]
    public function mttrByMonth(): array
    {
        return app(TrendAnalysisService::class)->getMttrByMonth($this->mttrMonthsBack, $this->scanTypeFilter);
    }

    #[Computed]
    public function mttrWindowOptions(): array
    {
        return [
            ['value' => '3', 'label' => 'Last Quarter'],
            ['value' => '6', 'label' => 'Half Year'],
            ['value' => '12', 'label' => 'Year'],
            ['value' => 'all', 'label' => 'All-time'],
        ];
    }

    #[Computed]
    public function newFindingsByPeriod(): array
    {
        return app(TrendAnalysisService::class)->getNewFindingsByPeriod(
            $this->newFindingsGranularity,
            $this->scanTypeFilter,
        );
    }

    #[Computed]
    public function scanTypeOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'Combined'],
            ['value' => 'github', 'label' => 'GitHub'],
            ['value' => 'azure', 'label' => 'Containers/Azure'],
        ];
    }

    #[Computed]
    public function scanTypeLabel(): string
    {
        return collect($this->scanTypeOptions)
            ->firstWhere('value', $this->scanTypeFilter)['label'] ?? 'Combined';
    }

    #[Computed]
    public function scanFreshness(): array
    {
        $latestGithub = app(PipelineRunRepository::class)->getLatestGithubRun();

        $latestContainer = app(PipelineRunRepository::class)->getLatestContainerRun();

        return [
            'github' => $this->freshnessItem($latestGithub?->ingested_at),
            'azure' => $this->freshnessItem($latestContainer?->ingested_at),
        ];
    }

    #[Computed]
    public function openFindingsGroupingOptions(): array
    {
        return [
            ['value' => 'month', 'label' => 'Per month'],
            ['value' => 'day', 'label' => 'Per day'],
        ];
    }

    #[Computed]
    public function openFindingsWindowOptions(): array
    {
        return [
            ['value' => 3, 'label' => 'Last 3 months'],
            ['value' => 6, 'label' => 'Last 6 months'],
            ['value' => 12, 'label' => 'Last 12 months'],
        ];
    }

    #[Computed]
    public function criticalFindingsWindowOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Last month'],
            ['value' => 3, 'label' => 'Last 3 months'],
            ['value' => 6, 'label' => 'Last 6 months'],
            ['value' => 12, 'label' => 'Last 12 months'],
        ];
    }

    #[Computed]
    public function newFindingsGroupingOptions(): array
    {
        return [
            ['value' => 'month', 'label' => 'Per month'],
            ['value' => 'day', 'label' => 'Per day (30 days)'],
        ];
    }

    #[Computed]
    public function trendGranularityOptions(): array
    {
        return [
            ['value' => 'month', 'label' => 'Month'],
            ['value' => 'week', 'label' => 'Week'],
            ['value' => 'day', 'label' => 'Day'],
        ];
    }

    #[Computed]
    public function trendComparisonLabel(): string
    {
        return match ($this->trendGranularity) {
            'day' => 'vs. yesterday',
            'week' => 'vs. last week',
            default => 'vs. last month',
        };
    }

    #[Computed]
    public function velocityGroupingOptions(): array
    {
        return [
            ['value' => 'day', 'label' => 'Per day'],
            ['value' => 'week', 'label' => 'Per quarter'],
            ['value' => 'month', 'label' => 'Per month'],
        ];
    }

    /**
     * @return array{
     *   subtitle: string,
     *   point_radius: int,
     *   hover_point_radius: int,
     *   x_max_ticks_limit: int|null,
     * }
     */
    #[Computed]
    public function velocityChartUi(): array
    {
        return [
            'subtitle' => match ($this->velocityGranularity) {
                'day' => 'Window: last 30 calendar days.',
                'week' => 'Window: last quarter (approx. 13 calendar weeks).',
                default => 'Window: approx. 7 calendar months.',
            },
            'point_radius' => match ($this->velocityGranularity) {
                'day' => 0,
                'week' => 2,
                default => 3,
            },
            'hover_point_radius' => match ($this->velocityGranularity) {
                'day' => 4,
                'week' => 5,
                default => 5,
            },
            'x_max_ticks_limit' => match ($this->velocityGranularity) {
                'day', 'week' => 14,
                default => null,
            },
        ];
    }

    public function updatedOpenFindingsMonthsBack(mixed $value): void
    {
        $n = (int) $value;

        $this->openFindingsMonthsBack = in_array($n, [3, 6, 12], true) ? $n : 6;
    }

    public function updatedCriticalFindingsMonthsBack(mixed $value): void
    {
        $n = (int) $value;

        $this->criticalFindingsMonthsBack = in_array($n, [1, 3, 6, 12], true) ? $n : 6;
    }

    public function updatedOpenFindingsGranularity(string $value): void
    {
        $this->openFindingsGranularity = in_array($value, ['month', 'day'], true) ? $value : 'month';
    }

    public function updatedNewFindingsGranularity(string $value): void
    {
        $this->newFindingsGranularity = in_array($value, ['month', 'day'], true) ? $value : 'month';
    }

    public function updatedTrendGranularity(string $value): void
    {
        $this->trendGranularity = in_array($value, ['month', 'week', 'day'], true) ? $value : 'month';
    }

    public function updatedVelocityGranularity(string $value): void
    {
        $this->velocityGranularity = in_array($value, ['month', 'week', 'day'], true) ? $value : 'month';
    }

    public function updatedMttrMonthsBack(mixed $value): void
    {
        $this->mttrMonthsBack = in_array((string) $value, ['3', '6', '12', 'all'], true) ? (string) $value : '6';
    }

    public function updatedScanTypeFilter(string $value): void
    {
        $this->scanTypeFilter = in_array($value, ['all', 'github', 'azure'], true) ? $value : 'all';
    }

    public function updatedCompareRepoA(mixed $value): void
    {
        $this->compareRepoA = $value ?: null;
    }

    public function updatedCompareRepoB(mixed $value): void
    {
        $this->compareRepoB = $value ?: null;
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::where('active', true)->get()->sortBy('name');
    }

    #[Computed]
    public function metricsA(): ?array
    {
        if (! $this->compareRepoA) {
            return null;
        }

        return app(TrendAnalysisService::class)->getRepositoryMetrics($this->compareRepoA, $this->scanTypeFilter);
    }

    #[Computed]
    public function metricsB(): ?array
    {
        if (! $this->compareRepoB) {
            return null;
        }

        return app(TrendAnalysisService::class)->getRepositoryMetrics($this->compareRepoB, $this->scanTypeFilter);
    }

    public function exportPdf(): StreamedResponse
    {
        return PdfExportService::generateTrendAnalysisPdf($this);
    }

    public function render(): View
    {
        return view('livewire.trend-analysis');
    }

    private function freshnessItem(mixed $timestamp): array
    {
        if ($timestamp === null) {
            return [
                'label' => 'Never',
                'title' => 'No scan found',
                'is_stale' => true,
            ];
        }

        $date = Carbon::parse($timestamp);

        return [
            'label' => $date->diffForHumans(),
            'title' => $date->format('d M Y, H:i'),
            'is_stale' => $date->lt(now()->subDays(7)),
        ];
    }
}
