@props(['services', 'compareRepoA', 'compareRepoB', 'metricsA', 'metricsB', 'scanTypeLabel' => 'Gecombineerd'])

{{-- Repository Comparison Tool Panel --}}
<div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Repository Comparison</p>
            <p class="text-xs text-slate-600">Compare statistics and security scores of two repositories
                side-by-side.</p>
        </div>
        <span
            class="inline-flex w-fit items-center gap-1.5 rounded-full border border-slate-700 bg-slate-950 px-2.5 py-1 text-[11px] font-medium text-slate-300"
        >
            <span class="size-1.5 rounded-full bg-cyan-300"></span>
            Source: {{ $scanTypeLabel }}
        </span>
    </div>

    {{-- Dropdown Selectors --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        <label class="flex flex-col gap-1">
            <span class="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
                Repository A
                <x-icon.spinner-arc
                    wire:loading
                    wire:target="compareRepoA"
                    class="size-3 animate-spin text-slate-400"
                />
            </span>
            <select
                wire:model.live="compareRepoA"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
            >
                <option value="">-- Select Repository A --</option>
                @foreach ($services as $service)
                    <option
                        value="{{ $service->_id }}"
                        @disabled($service->_id === $compareRepoB)
                    >
                        {{ $service->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
                Repository B
                <x-icon.spinner-arc
                    wire:loading
                    wire:target="compareRepoB"
                    class="size-3 animate-spin text-slate-400"
                />
            </span>
            <select
                wire:model.live="compareRepoB"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
            >
                <option value="">-- Select Repository B --</option>
                @foreach ($services as $service)
                    <option
                        value="{{ $service->_id }}"
                        @disabled($service->_id === $compareRepoA)
                    >
                        {{ $service->name }}
                    </option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($metricsA && $metricsB)
        {{-- Side-by-Side Active Comparison --}}
        <div class="mt-6 space-y-6">

            {{-- 1. Security Scores Gauges --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Gauge Repo A --}}
                <div class="flex items-center gap-6 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <div
                        class="relative flex-shrink-0"
                        x-data="{ score: {{ $metricsA['security_score'] }} }"
                        x-init="const circle = $refs.circle;
                        const circumference = 2 * Math.PI * 36;
                        circle.style.strokeDasharray = circumference;
                        const targetFrac = score / 100;
                        let current = 0;
                        let iv = setInterval(() => {
                            const step = Math.max(targetFrac / 35, 0.002);
                            current = Math.min(current + step, targetFrac);
                            circle.style.strokeDashoffset = circumference - current * circumference;
                            if (current >= targetFrac) clearInterval(iv);
                        }, 16);"
                    >
                        <svg
                            class="size-20 -rotate-90"
                            viewBox="0 0 80 80"
                        >
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                fill="none"
                                stroke="rgb(30,41,59)"
                                stroke-width="8"
                            />
                            <circle
                                x-ref="circle"
                                cx="40"
                                cy="40"
                                r="36"
                                fill="none"
                                stroke-width="8"
                                stroke-linecap="round"
                                x-show="score > 0"
                                style="stroke-dashoffset: 226; transition: stroke-dashoffset 0.05s linear;"
                                @class([
                                    'text-green-400' => $metricsA['security_score'] >= 80,
                                    'text-yellow-400' =>
                                        $metricsA['security_score'] >= 50 && $metricsA['security_score'] < 80,
                                    'text-red-400' => $metricsA['security_score'] < 50,
                                ])
                                stroke="currentColor"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-slate-100">{{ $metricsA['security_score'] }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Security Score</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-400">
                            {{ $services->firstWhere('_id', $compareRepoA)?->name }}</p>
                    </div>
                </div>

                {{-- Gauge Repo B --}}
                <div class="flex items-center gap-6 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <div
                        class="relative flex-shrink-0"
                        x-data="{ score: {{ $metricsB['security_score'] }} }"
                        x-init="const circle = $refs.circle;
                        const circumference = 2 * Math.PI * 36;
                        circle.style.strokeDasharray = circumference;
                        const targetFrac = score / 100;
                        let current = 0;
                        let iv = setInterval(() => {
                            const step = Math.max(targetFrac / 35, 0.002);
                            current = Math.min(current + step, targetFrac);
                            circle.style.strokeDashoffset = circumference - current * circumference;
                            if (current >= targetFrac) clearInterval(iv);
                        }, 16);"
                    >
                        <svg
                            class="size-20 -rotate-90"
                            viewBox="0 0 80 80"
                        >
                            <circle
                                cx="40"
                                cy="40"
                                r="36"
                                fill="none"
                                stroke="rgb(30,41,59)"
                                stroke-width="8"
                            />
                            <circle
                                x-ref="circle"
                                cx="40"
                                cy="40"
                                r="36"
                                fill="none"
                                stroke-width="8"
                                stroke-linecap="round"
                                x-show="score > 0"
                                style="stroke-dashoffset: 226; transition: stroke-dashoffset 0.05s linear;"
                                @class([
                                    'text-green-400' => $metricsB['security_score'] >= 80,
                                    'text-yellow-400' =>
                                        $metricsB['security_score'] >= 50 && $metricsB['security_score'] < 80,
                                    'text-red-400' => $metricsB['security_score'] < 50,
                                ])
                                stroke="currentColor"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-slate-100">{{ $metricsB['security_score'] }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Security Score</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-400">
                            {{ $services->firstWhere('_id', $compareRepoB)?->name }}</p>
                    </div>
                </div>
            </div>

            {{-- 2. Core Metrics Side-by-Side --}}
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/20">
                <div
                    class="grid grid-cols-3 border-b border-slate-800 bg-slate-950/60 px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    <div class="text-left">Metric</div>
                    <div class="truncate px-2">{{ $services->firstWhere('_id', $compareRepoA)?->name }}</div>
                    <div class="truncate px-2">{{ $services->firstWhere('_id', $compareRepoB)?->name }}</div>
                </div>
                <div class="divide-y divide-slate-800/40">
                    <div class="grid grid-cols-3 items-center px-4 py-3 text-center text-xs">
                        <div class="text-left font-medium text-slate-400">Open findings</div>
                        <div class="font-bold tabular-nums text-slate-200">{{ $metricsA['total_open'] }}</div>
                        <div class="font-bold tabular-nums text-slate-200">{{ $metricsB['total_open'] }}</div>
                    </div>
                    <div class="grid grid-cols-3 items-center px-4 py-3 text-center text-xs">
                        <div class="text-left font-medium text-slate-400">Critical SLA breaches</div>
                        <div @class([
                            'font-bold tabular-nums',
                            'text-red-400' => $metricsA['sla_breaches'] > 0,
                            'text-green-400' => $metricsA['sla_breaches'] === 0,
                        ])>
                            {{ $metricsA['sla_breaches'] }}
                        </div>
                        <div @class([
                            'font-bold tabular-nums',
                            'text-red-400' => $metricsB['sla_breaches'] > 0,
                            'text-green-400' => $metricsB['sla_breaches'] === 0,
                        ])>
                            {{ $metricsB['sla_breaches'] }}
                        </div>
                    </div>
                    <div class="grid grid-cols-3 items-center px-4 py-3 text-center text-xs">
                        <div class="text-left font-medium text-slate-400">Avg. Resolution Time (MTTR)</div>
                        <div class="font-bold tabular-nums text-slate-200">
                            {{ $metricsA['avg_mttr_days'] !== null ? $metricsA['avg_mttr_days'] . ' days' : '—' }}
                        </div>
                        <div class="font-bold tabular-nums text-slate-200">
                            {{ $metricsB['avg_mttr_days'] !== null ? $metricsB['avg_mttr_days'] . ' days' : '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Severity progress comparisons --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Repo A Severity progress --}}
                <div class="space-y-3 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Severity distribution (A)
                    </p>
                    <div class="space-y-2">
                        @foreach (\App\Enums\Severity::riskCases() as $severity)
                            @php
                                $sev = $severity->value;
                                $count = $metricsA['by_severity'][$sev] ?? 0;
                                $max = max(1, $metricsA['total_open']);
                                $pct = round(($count / $max) * 100);
                            @endphp
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400">{{ $severity->label() }}</span>
                                    <span class="font-bold tabular-nums text-slate-200">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded bg-slate-800">
                                    <div
                                        class="h-full rounded"
                                        style="width: {{ $pct }}%; background-color: {{ $severity->chartColor() }};"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Repo B Severity progress --}}
                <div class="space-y-3 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Severity distribution
                        (B)</p>
                    <div class="space-y-2">
                        @foreach (\App\Enums\Severity::riskCases() as $severity)
                            @php
                                $sev = $severity->value;
                                $count = $metricsB['by_severity'][$sev] ?? 0;
                                $max = max(1, $metricsB['total_open']);
                                $pct = round(($count / $max) * 100);
                            @endphp
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400">{{ $severity->label() }}</span>
                                    <span class="font-bold tabular-nums text-slate-200">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded bg-slate-800">
                                    <div
                                        class="h-full rounded"
                                        style="width: {{ $pct }}%; background-color: {{ $severity->chartColor() }};"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 4. Tool distribution comparisons --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Tools Repo A --}}
                <div class="space-y-3 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Open by scanner (A)</p>
                    @if (empty($metricsA['by_tool']))
                        <p class="text-xs italic text-slate-500">No open findings.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($metricsA['by_tool'] as $tool => $count)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300"
                                >
                                    <span class="size-1.5 rounded-full bg-cyan-400"></span>
                                    <span class="font-medium text-slate-400">{{ $tool }}:</span>
                                    <span class="font-bold tabular-nums text-slate-100">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Tools Repo B --}}
                <div class="space-y-3 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-400">Open by scanner (B)</p>
                    @if (empty($metricsB['by_tool']))
                        <p class="text-xs italic text-slate-500">No open findings.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($metricsB['by_tool'] as $tool => $count)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300"
                                >
                                    <span class="size-1.5 rounded-full bg-cyan-400"></span>
                                    <span class="font-medium text-slate-400">{{ $tool }}:</span>
                                    <span class="font-bold tabular-nums text-slate-100">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    @else
        {{-- Empty Placeholder state --}}
        <div
            class="mt-6 flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-800 bg-slate-950/20 px-4 py-12 text-center">
            <svg
                class="mb-3 size-12 text-slate-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.25"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"
                />
            </svg>
            <p class="text-xs font-semibold text-slate-400">Select Repositories</p>
            <p class="mt-1 max-w-xs text-[11px] text-slate-600">
                Choose two active repositories from the dropdown lists above to compare their statistics, open risks,
                and
                resolution times side-by-side.
            </p>
        </div>
    @endif
</div>
