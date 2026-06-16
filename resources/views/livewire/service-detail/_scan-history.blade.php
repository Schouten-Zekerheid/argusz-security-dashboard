@props(['scanHistory', 'serviceId'])

<div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-5 py-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-200">Scan History</h3>
            <p class="mt-0.5 text-xs text-slate-500">Latest run per branch</p>
        </div>
        @if (count($scanHistory) > 0)
            <a
                wire:navigate
                href="{{ route('services.runs', ['serviceId' => $serviceId, 'type' => $this->type]) }}"
                class="shrink-0 text-xs text-cyan-400 transition-colors hover:text-cyan-300"
            >
                All runs →
            </a>
        @endif
    </div>

    @forelse ($scanHistory as $group)
        {{-- Branch header --}}
        <div class="flex items-center justify-between gap-2 border-b border-slate-800/60 bg-slate-800/40 px-5 py-2">
            <span class="truncate font-mono text-xs text-slate-400">{{ $group['branch'] }}</span>
            @if ($group['has_more'])
                <a
                    wire:navigate
                    href="{{ route('services.runs', ['serviceId' => $serviceId, 'type' => $this->type]) }}?branch={{ urlencode($group['branch']) }}"
                    class="inline-block shrink-0 whitespace-nowrap rounded border border-slate-200 px-2 py-0.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
                >
                    +{{ $group['more_count'] }} more
                </a>
            @endif
        </div>

        {{-- Runs for this branch --}}
        @foreach ($group['runs'] as $run)
            <a
                wire:navigate
                href="{{ route('pipeline-runs.show', ['serviceId' => $serviceId, 'runId' => $run['run_id']]) }}"
                class="block border-b border-slate-800/60 px-5 py-3 transition-colors last:border-0 hover:bg-slate-800/30"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-medium text-slate-300">{{ $run['ingested_at'] }}</p>
                            @if ($run['ingested_at_diff'])
                                <span class="text-slate-700">·</span>
                                <span class="text-xs text-slate-500">{{ $run['ingested_at_diff'] }}</span>
                            @endif
                        </div>
                        <div class="mt-0.5 flex items-center gap-1.5">
                            @if ($run['actor'])
                                <span class="text-xs text-slate-600">{{ $run['actor'] }}</span>
                            @endif
                            @if ($run['commit_short'])
                                @if ($run['actor'])
                                    <span class="text-slate-700">·</span>
                                @endif
                                <span class="font-mono text-xs text-slate-600">{{ $run['commit_short'] }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="{{ $run['status_class'] }} shrink-0 text-xs font-semibold">
                        {{ $run['status_text'] }}
                    </span>
                </div>
            </a>
        @endforeach
    @empty
        <div class="px-5 py-8 text-center">
            <p class="text-xs text-slate-500">No scan history.</p>
        </div>
    @endforelse
</div>
