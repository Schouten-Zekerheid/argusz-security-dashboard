@props(['severityBreakdown', 'statCards', 'runMeta'])

<div class="grid grid-cols-4 gap-4">

    {{-- Severity Breakdown --}}
    <div class="shadow-xs col-span-2 rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Distribution</p>
        <div class="mt-3 space-y-2">
            @foreach ($severityBreakdown as $sev)
                <div class="flex items-center gap-2">
                    <span class="{{ $sev['text'] }} w-14 shrink-0 text-xs font-medium">{{ $sev['label'] }}</span>
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-800">
                        <div
                            class="{{ $sev['color'] }} h-full rounded-full"
                            style="width: {{ $sev['pct'] }}%"
                        ></div>
                    </div>
                    <span
                        class="{{ $sev['count'] > 0 ? 'text-slate-300' : 'text-slate-700' }} w-5 shrink-0 text-right font-mono text-xs"
                    >
                        {{ $sev['count'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Commit Hash --}}
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
            {{ $statCards['commit']['label'] }}
        </p>
        @if ($statCards['commit']['repo_url'] && $statCards['commit']['commit_hash'])
            <a
                href="{{ $statCards['commit']['repo_url'] }}/commit/{{ $statCards['commit']['commit_hash'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 block font-mono text-2xl font-bold text-slate-100 transition-colors hover:text-cyan-300"
            >
                {{ $statCards['commit']['value'] }}
            </a>
        @else
            <p class="mt-2 font-mono text-2xl font-bold text-slate-100">{{ $statCards['commit']['value'] }}</p>
        @endif
        @if ($runMeta['timestamp'])
            <p class="mt-1.5 text-xs text-slate-500">{{ $runMeta['timestamp'] }}</p>
        @endif
    </div>

    {{-- Findings + delta --}}
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
            {{ $statCards['findings']['label'] }}
        </p>
        <p class="mt-2 text-3xl font-bold text-slate-100">{{ $statCards['findings']['value'] }}</p>
        @if ($statCards['findings']['new_count'] > 0)
            <p class="mt-0.5 text-xs font-medium text-emerald-400">
                {{ $statCards['findings']['new_count'] }} new in this run
            </p>
        @endif
    </div>

</div>
