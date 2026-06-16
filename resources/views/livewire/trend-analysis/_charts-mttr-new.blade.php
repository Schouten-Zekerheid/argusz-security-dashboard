@props(['mttrByMonth', 'newFindingsGranularity', 'newFindingsByPeriod', 'newFindingsGroupingOptions'])

{{-- MTTR + new findings: wire:key is placed on the Alpine/canvas block itself (same pattern as critical repos). --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Mean Time to Resolution (MTTR) per month —
            closed findings</p>
        <div
            wire:key="trend-mttr-only-{{ crc32(json_encode($mttrByMonth)) }}"
            class="relative mt-4 h-56"
            x-data="{
                chart: null,
                initChart() {
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
                                    labels: @js(collect($mttrByMonth)->pluck('label')),
                                    datasets: [{
                                        label: 'Avg. days to resolution',
                                        data: @js(collect($mttrByMonth)->pluck('avg_days')),
                                        ...@js(config('chartjs.dataset.line_mttr')),
                                    }]
                                },
                                options: Object.assign({}, @include('components.chart.options-layout'), {
                                    interaction: {
                                        mode: 'index',
                                        intersect: false,
                                    },
                                    plugins: {
                                        legend: @include('components.chart.legend', ['variant' => 'hidden']),
                                        tooltip: Object.assign({}, @include('components.chart.tooltip-default'), {
                                            callbacks: {
                                                label: ctx => ctx.parsed.y + ' days',
                                            },
                                        }),
                                    },
                                    scales: @include('components.chart.scales-default', [
                                        'beginAtZero' => true,
                                    ]),
                                }),
                            });
                        } catch (e) {
                            console.warn('mttrChart:', e);
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
        @if (empty($mttrByMonth))
            <p class="mt-4 text-center text-sm text-slate-500">No resolved findings available.</p>
        @endif
    </div>

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">New findings per period</p>
            <div class="sm:w-44">
                <x-chart.granularity-select
                    id="newFindingsGranularitySelect"
                    wire:key="new-findings-granularity-select"
                    label="View"
                    :options="$newFindingsGroupingOptions"
                    wire:model.live="newFindingsGranularity"
                />
            </div>
        </div>
        <div
            wire:key="trend-new-findings-{{ $newFindingsGranularity }}-{{ crc32(json_encode($newFindingsByPeriod)) }}"
            class="relative mt-4 h-56"
            x-data="{
                chart: null,
                initChart() {
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
                                    labels: @js(collect($newFindingsByPeriod)->pluck('label')),
                                    datasets: [{
                                        label: 'New findings',
                                        data: @js(collect($newFindingsByPeriod)->pluck('count')),
                                        ...@js(config('chartjs.dataset.line_new_findings')),
                                    }]
                                },
                                options: Object.assign({}, @include('components.chart.options-layout'), {
                                    interaction: {
                                        mode: 'index',
                                        intersect: false,
                                    },
                                    plugins: {
                                        legend: @include('components.chart.legend', ['variant' => 'hidden']),
                                        tooltip: @include('components.chart.tooltip-default'),
                                    },
                                    scales: @include('components.chart.scales-default', [
                                        'yStepSize' => 1,
                                        'beginAtZero' => true,
                                        'xMaxTicksLimit' => $newFindingsGranularity === 'day' ? 12 : null,
                                    ]),
                                }),
                            });
                        } catch (e) {
                            console.warn('newFindingsChart:', e);
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
        @if (empty($newFindingsByPeriod))
            <p class="mt-4 text-center text-sm text-slate-500">No data available.</p>
        @endif
    </div>

</div>
