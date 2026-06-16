@props([
    'kpiSummary',
    'mttrMonthsBack',
    'mttrWindowOptions',
    'trendGranularity',
    'trendGranularityOptions',
    'trendComparisonLabel',
])

{{-- 4 KPI counter cards --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Open findings</p>
        <p
            class="mt-2 text-4xl font-bold tabular-nums text-slate-100"
            x-data="{ displayed: 0 }"
            x-init="let target = {{ $kpiSummary['total_open'] }};
            let step = Math.max(1, Math.ceil(target / 40));
            let iv = setInterval(() => {
                displayed = Math.min(displayed + step, target);
                if (displayed >= target) clearInterval(iv);
            }, 20);"
            x-text="displayed"
        ></p>
        <p class="mt-1 text-xs text-slate-500">total active</p>
    </div>

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Critical SLA breaches</p>
        <p
            @class([
                'mt-2 text-4xl font-bold tabular-nums',
                'text-red-400' => $kpiSummary['sla_breaches'] > 0,
                'text-green-400' => $kpiSummary['sla_breaches'] === 0,
            ])
            x-data="{ displayed: 0 }"
            x-init="let target = {{ $kpiSummary['sla_breaches'] }};
            let step = Math.max(1, Math.ceil(target / 40));
            let iv = setInterval(() => {
                displayed = Math.min(displayed + step, target);
                if (displayed >= target) clearInterval(iv);
            }, 20);"
            x-text="displayed"
        ></p>
        <p class="mt-1 text-xs text-slate-500">open for more than 7 days</p>
    </div>

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-center justify-between">
            <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider text-slate-400">
                Avg. resolution time (MTTR)
                <x-icon.spinner-arc
                    wire:loading
                    wire:target="mttrMonthsBack"
                    class="size-3 animate-spin text-slate-400"
                />
            </p>
            <select
                wire:model.live="mttrMonthsBack"
                class="cursor-pointer rounded-lg border border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[10px] font-medium text-slate-300 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500/30"
            >
                @foreach ($mttrWindowOptions as $option)
                    <option value="{{ $option['value'] }}">
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <p class="mt-2 text-4xl font-bold tabular-nums text-violet-300">
            {{ $kpiSummary['avg_mttr_days'] > 0 ? $kpiSummary['avg_mttr_days'] : '—' }}
        </p>
        <p class="mt-1 text-xs text-slate-500">days on average</p>
    </div>

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-center justify-between">
            <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider text-slate-400">
                Trend of open findings
                <x-icon.spinner-arc
                    wire:loading
                    wire:target="trendGranularity"
                    class="size-3 animate-spin text-slate-400"
                />
            </p>
            <select
                wire:model.live="trendGranularity"
                class="cursor-pointer rounded-lg border border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[10px] font-medium text-slate-300 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500/30"
            >
                @foreach ($trendGranularityOptions as $option)
                    <option value="{{ $option['value'] }}">
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mt-2 flex items-end gap-2">
            @if ($kpiSummary['trend_direction'] === 'up')
                <p class="text-4xl font-bold tabular-nums text-green-400">+{{ $kpiSummary['trend_pct'] }}%</p>
                <svg
                    class="mb-1 size-6 text-green-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 17L17 7M17 7H7M17 7v10"
                    />
                </svg>
            @elseif ($kpiSummary['trend_direction'] === 'down')
                <p class="text-4xl font-bold tabular-nums text-red-400">{{ $kpiSummary['trend_pct'] }}%</p>
                <svg
                    class="mb-1 size-6 text-red-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M7 7l10 10M17 17H7M17 17V7"
                    />
                </svg>
            @else
                <p class="text-4xl font-bold tabular-nums text-slate-400">—</p>
            @endif
        </div>
        <p class="mt-1 text-xs text-slate-500">{{ $trendComparisonLabel }}</p>
    </div>

</div>
