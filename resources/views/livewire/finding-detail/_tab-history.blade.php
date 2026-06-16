@props(['history'])

<div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="border-b border-slate-800 px-5 py-4">
        <h2 class="text-sm font-semibold text-slate-200">Status History</h2>
    </div>

    @if (count($history) === 0)
        <div class="px-5 py-8 text-center text-sm text-slate-500">
            No status changes found.
        </div>
    @else
        <ol class="divide-y divide-slate-800">
            @foreach ($history as $entry)
                <li class="flex items-start gap-4 px-5 py-4">
                    <div class="mt-0.5 size-2 shrink-0 rounded-full bg-indigo-500/60 ring-2 ring-indigo-500/20">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-medium text-slate-300">{{ $entry['from'] }}</span>
                            <x-icon.chevron-right class="size-3.5 shrink-0 text-slate-600" />
                            <span class="font-medium text-slate-100">{{ $entry['to'] }}</span>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-500">
                            <span>{{ $entry['at'] }}</span>
                            <span>by <span class="font-mono text-slate-400">{{ $entry['by'] }}</span></span>
                        </div>
                        @if (!empty($entry['reason']))
                            <p class="mt-1.5 text-xs italic text-slate-400">
                                <span class="not-italic text-slate-500">Reason:</span> {{ $entry['reason'] }}
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
