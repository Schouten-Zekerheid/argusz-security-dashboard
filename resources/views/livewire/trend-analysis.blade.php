<div
    class="space-y-6"
    wire:init="loadData"
>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Trend Analysis</h1>
            <p class="mt-1 text-sm text-slate-400">
                Findings over time, per repository and by type.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:items-end">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    wire:click="exportPdf"
                    wire:loading.attr="disabled"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs font-medium text-slate-200 transition-colors hover:bg-slate-800 hover:text-white focus:outline-none disabled:opacity-50"
                >
                    <svg
                        wire:loading
                        wire:target="exportPdf"
                        class="size-3.5 animate-spin text-slate-200"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <svg
                        wire:loading.remove
                        wire:target="exportPdf"
                        class="size-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <span
                        wire:loading
                        wire:target="exportPdf"
                    >Exporting...</span>
                    <span
                        wire:loading.remove
                        wire:target="exportPdf"
                    >Export PDF</span>
                </button>

                <select
                    wire:model.live="scanTypeFilter"
                    class="cursor-pointer rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                >
                    @foreach ($this->scanTypeOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap justify-start gap-2 text-[11px] sm:justify-end">
                @if ($scanTypeFilter !== 'azure')
                    <span
                        title="{{ $this->scanFreshness['github']['title'] }}"
                        @class([
                            'inline-flex items-center gap-1 rounded-full border px-2 py-1',
                            'border-amber-400/30 bg-amber-500/10 text-amber-200' =>
                                $this->scanFreshness['github']['is_stale'],
                            'border-slate-700 bg-slate-900 text-slate-400' => !$this->scanFreshness[
                                'github'
                            ]['is_stale'],
                        ])
                    >
                        <span class="size-1.5 rounded-full bg-current"></span>
                        Github: {{ $this->scanFreshness['github']['label'] }}
                    </span>
                @endif

                @if ($scanTypeFilter !== 'github')
                    <span
                        title="{{ $this->scanFreshness['azure']['title'] }}"
                        @class([
                            'inline-flex items-center gap-1 rounded-full border px-2 py-1',
                            'border-amber-400/30 bg-amber-500/10 text-amber-200' =>
                                $this->scanFreshness['azure']['is_stale'],
                            'border-slate-700 bg-slate-900 text-slate-400' => !$this->scanFreshness[
                                'azure'
                            ]['is_stale'],
                        ])
                    >
                        <span class="size-1.5 rounded-full bg-current"></span>
                        Containers/Azure: {{ $this->scanFreshness['azure']['label'] }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if ($loading)
        @include('livewire.trend-analysis._loading')
    @else
        {{-- Elk blok eigen wire:key voorkomt dat Livewire <canvas>-nodes tussen secties morpht. --}}

        <div wire:key="trend-slot-score-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._security-score', [
                'overallSecurityScore' => $this->overallSecurityScore,
            ])
        </div>

        <div wire:key="trend-slot-kpi-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._kpi-cards', [
                'kpiSummary' => $this->kpiSummary,
                'mttrMonthsBack' => $mttrMonthsBack,
                'mttrWindowOptions' => $this->mttrWindowOptions,
                'trendGranularity' => $trendGranularity,
                'trendGranularityOptions' => $this->trendGranularityOptions,
                'trendComparisonLabel' => $this->trendComparisonLabel,
            ])
        </div>

        <div wire:key="trend-slot-open-findings-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._charts-top', [
                'monthlyOpenFindings' => $this->monthlyOpenFindings,
                'findingsByTool' => $this->findingsByTool,
                'findingsByPurpose' => $this->findingsByPurpose,
                'openFindingsGroupingOptions' => $this->openFindingsGroupingOptions,
                'openFindingsWindowOptions' => $this->openFindingsWindowOptions,
            ])
        </div>

        <div wire:key="trend-slot-critical-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._chart-critical-repos', [
                'criticalFindingsTrend' => $this->criticalFindingsTrend,
                'criticalFindingsMonthsBack' => $criticalFindingsMonthsBack,
                'criticalFindingsWindowOptions' => $this->criticalFindingsWindowOptions,
            ])
        </div>

        <div wire:key="trend-slot-mttr-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._charts-mttr-new', [
                'mttrByMonth' => $this->mttrByMonth,
                'newFindingsGranularity' => $newFindingsGranularity,
                'newFindingsByPeriod' => $this->newFindingsByPeriod,
                'newFindingsGroupingOptions' => $this->newFindingsGroupingOptions,
            ])
        </div>

        <div wire:key="trend-slot-velocity-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._charts-risk-velocity', [
                'riskScoreByRepository' => $this->riskScoreByRepository,
                'findingsVelocity' => $this->findingsVelocity,
                'velocityGranularity' => $velocityGranularity,
                'velocityGroupingOptions' => $this->velocityGroupingOptions,
                'velocityChartUi' => $this->velocityChartUi,
            ])
        </div>

        <div wire:key="trend-slot-sla-{{ $scanTypeFilter }}">
            @include('livewire.trend-analysis._chart-sla-aging', [
                'slaAging' => $this->slaAging,
            ])
        </div>

        <div wire:key="trend-slot-comparison-{{ $scanTypeFilter }}-{{ $compareRepoA }}-{{ $compareRepoB }}">
            @include('livewire.trend-analysis._repository-comparison', [
                'services' => $this->services,
                'compareRepoA' => $compareRepoA,
                'compareRepoB' => $compareRepoB,
                'metricsA' => $this->metricsA,
                'metricsB' => $this->metricsB,
                'scanTypeLabel' => $this->scanTypeLabel,
            ])
        </div>
    @endif

</div>

@push('scripts')
    @vite(['resources/js/trend-analysis.js'])
@endpush
