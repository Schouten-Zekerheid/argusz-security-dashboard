@props([
    'monthlyOpenFindings',
    'findingsByTool',
    'findingsByPurpose',
    'openFindingsGroupingOptions',
    'openFindingsWindowOptions',
])

{{-- Key on Alpine root (= canvas block): same pattern as critical repo chart, so that x-init does not morph "in-place" without reboot. --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5 xl:col-span-2">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Open findings (post-creation,
                    still open)</p>
                <p class="mt-0.5 text-xs text-slate-600">Period is determined by the dropdown selection; axis shows day
                    or month clusters.</p>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:auto-cols-fr sm:grid-flow-col sm:grid-rows-1 md:gap-4">
                <div class="sm:min-w-[9rem]">
                    <x-chart.granularity-select
                        id="openFindingsGranularitySelect"
                        wire:key="open-findings-granularity-select"
                        label="Grouping"
                        :options="$openFindingsGroupingOptions"
                        wire:model.live="openFindingsGranularity"
                    />
                </div>
                <div class="sm:min-w-[12rem]">
                    <x-chart.granularity-select
                        id="openFindingsMonthsBackSelect"
                        wire:key="open-findings-months-back-select"
                        label="Window"
                        :options="$openFindingsWindowOptions"
                        wire:model.live.number="openFindingsMonthsBack"
                    />
                </div>
            </div>
        </div>
        <div
            wire:key="trend-open-line-{{ $openFindingsGranularity }}-{{ $openFindingsMonthsBack }}-{{ crc32(json_encode($monthlyOpenFindings)) }}"
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
                                    labels: @js(collect($monthlyOpenFindings)->pluck('label')),
                                    datasets: [{
                                        label: 'Open findings',
                                        data: @js(collect($monthlyOpenFindings)->pluck('count')),
                                        ...@js(config('chartjs.dataset.line_open_findings_monthly')),
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
                                        'xMaxTicksLimit' => $openFindingsGranularity === 'day' ? 14 : 12,
                                    ]),
                                }),
                            });
                        } catch (e) {
                            console.warn('openFindingsChart:', e);
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
        @if (empty($monthlyOpenFindings))
            <p class="mt-4 text-center text-sm text-slate-500">No data available.</p>
        @endif
    </div>

    <div
        class="shadow-xs flex flex-col justify-between rounded-xl border border-slate-800 bg-slate-900 p-5"
        wire:key="trend-open-doughnut-{{ crc32(json_encode($findingsByTool)) }}-{{ crc32(json_encode($findingsByPurpose)) }}"
        x-data="{
            chart: null,
            legendItems: @js($findingsByPurpose).map(item => ({ ...item, hidden: false })),
            legendColors: @js(config('chartjs.palette.doughnut_segments')),
            capitalize(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            },
            toggleSegment(index) {
                const idx = Number(index);
                // Haal de ruwe (niet-geproxyde) Chart.js-instantie op via de canvas ref om conflicten met Alpine's reactiviteit/Proxy-systeem te voorkomen.
                const chartInstance = Chart.getChart(this.$refs.canvas);
                if (!chartInstance) return;
                try {
                    chartInstance.toggleDataVisibility(idx);
                    chartInstance.update();
                } catch (e) {
                    console.warn('Error toggling segment visibility:', e);
                }
                this.legendItems = this.legendItems.map((item, i) =>
                    i === idx ? { ...item, hidden: !item.hidden } : item
                );
            },
            initChart() {
                queueMicrotask(() => {
                    try {
                        const canvas = this.$refs.canvas;
                        if (!canvas) {
                            return;
                        }
                        Chart.getChart(canvas)?.destroy();
                        this.chart = new Chart(canvas, {
                            type: 'doughnut',
                            data: {
                                labels: @js(collect($findingsByPurpose)->pluck('purpose')),
                                datasets: [{
                                    data: @js(collect($findingsByPurpose)->pluck('count')),
                                    backgroundColor: this.legendColors,
                                    ...@js(config('chartjs.dataset.doughnut')),
                                }]
                            },
                            options: Object.assign({}, @include('components.chart.options-layout'), {
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: @include('components.chart.tooltip-default'),
                                },
                            }),
                        });
                    } catch (e) {
                        console.warn('findingsByToolChart:', e);
                    }
                });
            }
        }"
        x-init="initChart()"
    >
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Findings by type</p>
            <div class="relative mt-4 flex h-48 items-center justify-center">
                <canvas
                    x-ref="canvas"
                    wire:ignore
                ></canvas>
            </div>
        </div>

        <div
            class="mt-4 flex flex-wrap justify-center gap-x-3 gap-y-2"
            x-show="legendItems.length > 0"
        >
            <template
                x-for="(label, idx) in legendItems"
                :key="label.purpose"
            >
                <button
                    @click.prevent="toggleSegment(idx)"
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-300 transition-all hover:border-slate-700 hover:bg-slate-900 focus:outline-none"
                    :class="label.hidden ? 'opacity-50' : 'opacity-100'"
                >
                    <!-- Custom HTML Checkbox -->
                    <span
                        class="flex size-4 shrink-0 items-center justify-center rounded-[4px] border transition-all"
                        :style="label.hidden ?
                            'border-color: ' + (legendColors[idx] || '#64748b') + '; background-color: transparent;' :
                            'background-color: ' + (legendColors[idx] || '#64748b') + '; border-color: ' + (
                                legendColors[idx] || '#64748b') + ';'"
                    >
                        <svg
                            x-show="!label.hidden"
                            class="size-2.5 stroke-[4px] text-slate-950"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </span>

                    <span
                        :class="label.hidden ? 'line-through text-slate-500' : 'text-slate-300'"
                        x-text="capitalize(label.purpose)"
                    ></span>
                    <span
                        class="font-mono text-[10px] text-slate-500"
                        x-text="'(' + label.count + ')'"
                    ></span>
                </button>
            </template>
        </div>
        <p
            class="mt-4 text-center text-sm text-slate-500"
            x-show="legendItems.length === 0"
        >No data available.</p>
    </div>

</div>
