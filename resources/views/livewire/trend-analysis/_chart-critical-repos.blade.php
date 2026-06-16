@props(['criticalFindingsTrend', 'criticalFindingsMonthsBack', 'criticalFindingsWindowOptions'])

{{-- Trend of critical open findings per repository over time --}}
<div
    class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5"
    x-data="{
        chart: null,
        isStacked: false,
        currentLabels: [],
        currentDatasets: [],
        retryCount: 0,
        initChart(labels, datasets) {
            if (labels) this.currentLabels = labels;
            if (datasets) this.currentDatasets = datasets;
    
            if (typeof window.Argusz?.createChartCheckboxIcon !== 'function' || typeof Chart !== 'function') {
                this.retryCount = (this.retryCount || 0) + 1;
                if (this.retryCount > 1000) {
                    console.error('Argusz chart loading failed: maximum retries reached (1000).');
                    return;
                }
                setTimeout(() => this.initChart(), 30);
                return;
            }
            this.retryCount = 0;
    
            queueMicrotask(() => {
                try {
                    const canvas = this.$refs.canvas;
                    if (!canvas) {
                        return;
                    }
                    Chart.getChart(canvas)?.destroy();
                    const isStacked = this.isStacked;
    
                    const datasetsToUse = this.currentDatasets.map(ds => {
                        return Object.assign({}, ds, {
                            fill: isStacked ? 'origin' : false,
                        });
                    });
    
                    const scales = Object.assign({}, @include('components.chart.scales-default', [
                        'yStepSize' => 1,
                        'beginAtZero' => true,
                        'xMaxTicksLimit' => $criticalFindingsMonthsBack === 12 ? 12 : null,
                    ]));
                    scales.y.stacked = isStacked;
                    scales.x.stacked = isStacked;
    
                    this.chart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: this.currentLabels,
                            datasets: datasetsToUse,
                        },
                        options: Object.assign({}, @include('components.chart.options-layout'), {
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: Object.assign({}, @include('components.chart.legend', ['variant' => 'top']), {
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 14,
                                        generateLabels: function(chart) {
                                            const labels = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                                            labels.forEach(label => {
                                                const isVisible = chart.isDatasetVisible(label.datasetIndex);
                                                label.pointStyle = window.Argusz.createChartCheckboxIcon(label.strokeStyle || label.fillStyle, isVisible);
                                            });
                                            return labels;
                                        }
                                    },
                                    onHover: (e, legendItem, legend) => {
                                        const canvas = legend.chart.canvas;
                                        canvas.style.cursor = 'pointer';
                                    },
                                    onLeave: (e, legendItem, legend) => {
                                        const canvas = legend.chart.canvas;
                                        canvas.style.cursor = 'default';
                                    }
                                }),
                                tooltip: Object.assign({}, @include('components.chart.tooltip-default'), {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.dataset.label || '';
                                            const val = context.parsed.y;
                                            if (isStacked) {
                                                let stackedVal = 0;
                                                for (let i = 0; i <= context.datasetIndex; i++) {
                                                    if (context.chart.isDatasetVisible(i)) {
                                                        const dataPoint = context.chart.data.datasets[i].data[context.dataIndex];
                                                        if (dataPoint !== undefined && dataPoint !== null) {
                                                            stackedVal += dataPoint;
                                                        }
                                                    }
                                                }
                                                return `${label}: ${val} (cumulative: ${stackedVal})`;
                                            }
                                            return `${label}: ${val}`;
                                        },
                                        footer: function(tooltipItems) {
                                            let sum = 0;
                                            tooltipItems.forEach(item => {
                                                sum += (item.parsed.y || 0);
                                            });
                                            return 'Total: ' + sum;
                                        }
                                    }
                                }),
                            },
                            scales: scales,
                        }),
                    });
                } catch (e) {
                    console.warn('criticalRepoTrendChart:', e);
                }
            });
        },
        updateChartStacked(stacked) {
            this.isStacked = stacked;
            this.initChart();
        }
    }"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Trend of critical open findings
                per repository</p>
            <p
                class="mt-0.5 text-xs text-slate-600"
                x-text="isStacked ? 'Cumulative (stacked) number of open critical findings over time per repository.' : 'Number of open critical findings over time per repository.'"
            >
                Number of open critical findings over time per repository.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4 sm:justify-end">
            <!-- Weergave Toggle -->
            <div class="space-y-1">
                <span
                    class="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">View</span>
                <div class="flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 p-1">
                    <button
                        type="button"
                        @click="updateChartStacked(false)"
                        class="cursor-pointer rounded-md bg-transparent px-3 py-1 text-xs font-medium transition-all"
                        :class="!isStacked ? 'bg-slate-800 text-slate-100' : 'text-slate-400 hover:text-slate-200'"
                    >
                        Absolute
                    </button>
                    <button
                        type="button"
                        @click="updateChartStacked(true)"
                        class="cursor-pointer rounded-md bg-transparent px-3 py-1 text-xs font-medium transition-all"
                        :class="isStacked ? 'bg-slate-800 text-slate-100' : 'text-slate-400 hover:text-slate-200'"
                    >
                        Cumulative
                    </button>
                </div>
            </div>

            <!-- Venster Selector -->
            <label class="space-y-1 sm:w-56">
                <span class="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
                    Window
                    <x-icon.spinner-arc
                        wire:loading
                        wire:target="criticalFindingsMonthsBack"
                        class="size-3 animate-spin text-slate-400"
                    />
                </span>
                <select
                    id="criticalFindingsMonthsBackSelect"
                    wire:key="critical-findings-months-back-select"
                    wire:model.live.number="criticalFindingsMonthsBack"
                    class="w-full cursor-pointer rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                >
                    @foreach ($criticalFindingsWindowOptions as $option)
                        <option value="{{ $option['value'] }}">
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    @if (filled($criticalFindingsTrend['datasets'] ?? []))
        <div
            wire:key="crit-trend-chart-{{ $criticalFindingsMonthsBack }}-{{ crc32(json_encode($criticalFindingsTrend)) }}"
            class="relative mt-4 h-64"
            x-init="initChart(@js($criticalFindingsTrend['labels']), @js($criticalFindingsTrend['datasets']))"
        >
            <canvas
                x-ref="canvas"
                wire:ignore
            ></canvas>
        </div>
    @else
        <div
            wire:key="crit-trend-empty-{{ $criticalFindingsMonthsBack }}"
            class="relative mt-4 flex h-64 items-center justify-center rounded-lg border border-dashed border-slate-800 bg-slate-950/40"
        >
            <p class="px-4 text-center text-sm text-slate-500">
                No critical open findings in the selected period.
            </p>
        </div>
    @endif
</div>
