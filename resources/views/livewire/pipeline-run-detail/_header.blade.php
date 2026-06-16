@props(['serviceId', 'service', 'runId', 'statCards', 'runMeta'])

{{-- Breadcrumb --}}
<x-breadcrumb>
    <a
        wire:navigate
        href="{{ route('dashboard') }}"
        class="transition-colors hover:text-slate-300"
    >
        Services
    </a>
    <span>/</span>
    <a
        wire:navigate
        href="{{ route('services.show', $serviceId) }}"
        class="transition-colors hover:text-slate-300"
    >
        {{ $service->name }}
    </a>
    <span>/</span>
    <span class="font-mono text-slate-300">Run #{{ substr($runId, -8) }}</span>
</x-breadcrumb>

{{-- Page header --}}
<div class="flex items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="font-mono text-2xl font-bold text-slate-100">Run #{{ substr($runId, -8) }}</h1>
            <span @class([
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/40' =>
                    (int) ($statCards['findings']['value'] ?? 0) > 0,
                'bg-green-500/20 text-green-300 ring-1 ring-green-400/40' =>
                    (int) ($statCards['findings']['value'] ?? 0) <= 0,
            ])>
                {{ (int) ($statCards['findings']['value'] ?? 0) > 0 ? 'ISSUES FOUND' : 'CLEAN' }}
            </span>
        </div>
        <div class="mt-1.5 flex items-center gap-2 text-sm text-slate-500">
            @if ($runMeta['actor'])
                <span>Started by: <span class="text-slate-400">{{ $runMeta['actor'] }}</span></span>
            @endif
            @if ($runMeta['actor'] && ($runMeta['pr_number'] || $runMeta['branch']))
                <span class="text-slate-700">|</span>
            @endif
            @if ($runMeta['pr_number'])
                <span>PR:
                    @if ($runMeta['pr_url'])
                        <a
                            href="{{ $runMeta['pr_url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-cyan-400 transition-colors hover:text-cyan-300"
                        >#{{ $runMeta['pr_number'] }}</a>
                    @else
                        <span class="text-slate-400">#{{ $runMeta['pr_number'] }}</span>
                    @endif
                </span>
            @elseif ($runMeta['branch'])
                <span>Branch: <span class="font-mono text-slate-400">{{ $runMeta['branch'] }}</span></span>
            @endif
        </div>
    </div>

    <a
        wire:navigate
        href="{{ route('services.show', $serviceId) }}"
        class="flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-400 transition-colors hover:border-slate-500 hover:text-slate-200"
    >
        <svg
            class="size-3.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
        </svg>
        Back to service
    </a>
</div>
