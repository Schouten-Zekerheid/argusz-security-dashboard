@props(['stats', 'serviceId', 'source', 'severityBreakdown'])

<div class="grid grid-cols-3 gap-4">

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Open findings</p>
        @if ($stats['no_default_branch_scan'])
            <p class="mt-1 text-3xl font-bold text-slate-500">—</p>
            <p class="mt-2 text-sm font-medium text-slate-500">Not scanned yet</p>
        @elseif ($stats['open_count'] > 0)
            <a
                wire:navigate
                href="{{ route('findings', ['service' => $serviceId, 'status' => 'open,returning', 'source' => $source]) }}"
                class="mt-1 text-3xl font-bold text-slate-100 transition-colors hover:text-slate-300"
            >
                {{ $stats['open_count'] }}
            </a>
            @if ($stats['critical_count'] > 0)
                <a
                    wire:navigate
                    href="{{ route('findings', ['service' => $serviceId, 'severity' => \App\Enums\Severity::Critical->value, 'status' => 'open,returning', 'source' => $source]) }}"
                    class="text-severity-critical mt-2 flex items-center gap-1 text-sm font-medium transition-colors hover:text-red-300"
                >
                    <svg
                        class="size-3.5 shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                        />
                    </svg>
                    {{ $stats['critical_count'] }} critical
                </a>
            @endif
        @else
            <p class="mt-1 text-3xl font-bold text-slate-100">0</p>
            <p class="mt-2 text-sm font-medium text-green-400">No issues found</p>
        @endif
    </div>

    <div class="shadow-xs col-span-2 rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Distribution</p>
        <div class="mt-3 flex h-2.5 w-full overflow-hidden rounded-full bg-slate-800">
            @foreach ($severityBreakdown as $sev)
                @if ($sev['count'] > 0)
                    <div
                        class="{{ $sev['color'] }}"
                        style="flex: {{ $sev['count'] }} 0 0"
                    ></div>
                @endif
            @endforeach
        </div>
        <div class="mt-2.5 flex w-full">
            @foreach ($severityBreakdown as $sev)
                @if ($sev['count'] > 0)
                    <div
                        class="flex flex-col items-center"
                        style="flex: {{ $sev['count'] }} 0 0"
                    >
                        <p class="{{ $sev['text'] }} text-xs font-bold">{{ $sev['count'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $sev['label'] }}</p>
                    </div>
                @endif
            @endforeach
        </div>
        @if (collect($severityBreakdown)->contains('count', 0))
            <div class="mt-1.5 flex gap-3">
                @foreach ($severityBreakdown as $sev)
                    @if ($sev['count'] === 0)
                        <span class="text-xs text-slate-600">{{ $sev['label'] }}: 0</span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

</div>
