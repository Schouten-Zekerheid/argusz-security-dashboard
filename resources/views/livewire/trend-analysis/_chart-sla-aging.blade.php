@props(['slaAging'])

{{-- SLA aging — key on Alpine root instead of only outer card (same as critical repo chart). --}}
<div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">SLA aging — open findings by age</p>
    <div
        wire:key="trend-sla-aging-inner-{{ crc32(json_encode($slaAging)) }}"
        class="relative mt-4 h-56"
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
                            type: 'bar',
                            data: {
                                labels: @js($slaAging['buckets']),
                                datasets: @js(
    collect(\App\Enums\Severity::riskCases())
        ->map(
            fn($severity) => [
                'label' => $severity->label(),
                'data' => collect($slaAging['buckets'])->map(fn($b) => $slaAging['data'][$b][$severity->value]),
                'backgroundColor' => config('chartjs.palette.sla.' . strtolower($severity->value)),
            ],
        )
        ->map(fn($dataset) => array_merge($dataset, config('chartjs.dataset.sla_segment')))
        ->values(),
)
                            },
                            options: Object.assign({}, @include('components.chart.options-layout'), {
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
                                    'stacked' => true,
                                    'xTickFontSize' => 12,
                                    'yStepSize' => 1,
                                    'beginAtZero' => true,
                                ]),
                            }),
                        });
                    } catch (e) {
                        console.warn('slaAgingChart:', e);
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
</div>
