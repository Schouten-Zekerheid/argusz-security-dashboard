@props([
    'riskScoreByRepository',
    'findingsVelocity',
    'velocityGranularity' => 'month',
    'velocityGroupingOptions',
    'velocityChartUi',
])

{{-- Risk list + velocity: each in its own card against canvas cross-morph. --}}

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div
        wire:key="trend-risk-repos-{{ crc32(json_encode($riskScoreByRepository)) }}"
        class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5"
    >
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Risk score per repository</p>
        <p class="mt-0.5 text-xs text-slate-600">Weighted score: critical × 10 + high × 5 + medium × 2 + low × 1</p>
        <div class="mt-4 space-y-3">
            @forelse ($riskScoreByRepository as $item)
                <div>
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <span class="truncate text-sm text-slate-200">{{ $item['repository'] }}</span>
                        <span @class([
                            'shrink-0 text-xs font-bold tabular-nums',
                            'text-red-400' => $item['pct'] >= 75,
                            'text-orange-400' => $item['pct'] >= 40 && $item['pct'] < 75,
                            'text-amber-400' => $item['pct'] >= 15 && $item['pct'] < 40,
                            'text-green-400' => $item['pct'] < 15,
                        ])>
                            {{ $item['score'] }}
                        </span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-800">
                        <div
                            @class([
                                'h-full rounded-full transition-all duration-700',
                                'bg-red-500' => $item['pct'] >= 75,
                                'bg-orange-500' => $item['pct'] >= 40 && $item['pct'] < 75,
                                'bg-amber-400' => $item['pct'] >= 15 && $item['pct'] < 40,
                                'bg-green-500' => $item['pct'] < 15,
                            ])
                            style="width: {{ $item['pct'] }}%"
                        ></div>
                    </div>
                    <div class="mt-1 flex gap-3">
                        @if ($item['critical'] > 0)
                            <span class="text-xs text-red-400">C: {{ $item['critical'] }}</span>
                        @endif
                        @if ($item['high'] > 0)
                            <span class="text-xs text-orange-400">H: {{ $item['high'] }}</span>
                        @endif
                        @if ($item['medium'] > 0)
                            <span class="text-xs text-amber-400">M: {{ $item['medium'] }}</span>
                        @endif
                        @if ($item['low'] > 0)
                            <span class="text-xs text-lime-400">L: {{ $item['low'] }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No open findings.</p>
            @endforelse
        </div>
    </div>

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                    Findings velocity — new vs. resolved
                </p>
                <p class="mt-1 text-xs text-slate-600">{{ $velocityChartUi['subtitle'] }}</p>
            </div>
            <div class="sm:w-[12.5rem]">
                <x-chart.granularity-select
                    id="velocityGranularitySelect"
                    wire:key="velocity-granularity-select"
                    label="Grouping"
                    :options="$velocityGroupingOptions"
                    wire:model.live="velocityGranularity"
                />
            </div>
        </div>
        <div
            wire:key="trend-velocity-chart-{{ $velocityGranularity }}-{{ crc32(json_encode($findingsVelocity)) }}"
            class="relative mt-4 h-72"
            x-data="{
                chart: null,
                retryCount: 0,
                initChart() {
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
                            this.chart = new Chart(canvas, {
                                type: 'line',
                                data: {
                                    labels: @js(collect($findingsVelocity)->pluck('label')),
                                    datasets: [
                                        Object.assign({}, {
                                                label: 'New',
                                                data: @js(collect($findingsVelocity)->pluck('new')),
                                                order: 0,
                                                pointRadius: @js($velocityChartUi['point_radius']),
                                                pointHoverRadius: @js($velocityChartUi['hover_point_radius']),
                                                cubicInterpolationMode: 'monotone',
                                            },
                                            @js(config('chartjs.dataset.velocity_line_new'))),
                                        Object.assign({}, {
                                                label: 'Resolved',
                                                data: @js(collect($findingsVelocity)->pluck('resolved')),
                                                order: 1,
                                                pointRadius: @js($velocityChartUi['point_radius']),
                                                pointHoverRadius: @js($velocityChartUi['hover_point_radius']),
                                                cubicInterpolationMode: 'monotone',
                                            },
                                            @js(config('chartjs.dataset.velocity_line_resolved'))),
                                    ]
                                },
                                options: Object.assign({}, @include('components.chart.options-layout'), {
                                    interaction: {
                                        mode: 'index',
                                        intersect: false,
                                    },
                                    elements: {
                                        line: {
                                            spanGaps: false,
                                            borderJoinStyle: 'round',
                                        },
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
                                                        label.pointStyle = window.Argusz.createChartCheckboxIcon(label.fillStyle, isVisible);
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
                                        tooltip: @include('components.chart.tooltip-default'),
                                    },
                                    scales: @include('components.chart.scales-default', [
                                        'yStepSize' => 1,
                                        'beginAtZero' => false,
                                        'xMaxTicksLimit' => $velocityChartUi['x_max_ticks_limit'],
                                    ]),
                                }),
                            });
                        } catch (e) {
                            console.warn('velocityChart:', e);
                        }
                    });
                },
            }"
            x-init="initChart()"
        >
            <canvas
                x-ref="canvas"
                wire:ignore
            ></canvas>
        </div>
        @if (empty($findingsVelocity))
            <p class="mt-4 text-center text-sm text-slate-500">No data available.</p>
        @endif
    </div>

</div>
