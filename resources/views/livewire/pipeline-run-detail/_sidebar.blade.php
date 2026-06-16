@props(['runMeta', 'fixedFindings'])

{{-- Run Metadata --}}
<div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="border-b border-slate-800 px-5 py-4">
        <h3 class="text-sm font-semibold text-slate-200">Run metadata</h3>
    </div>
    <div class="space-y-3 px-5 py-4">

        @if ($runMeta['service'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Service</p>
                <p class="mt-0.5 text-sm text-slate-300">{{ $runMeta['service'] }}</p>
            </div>
        @endif

        @if ($runMeta['repository'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Repository</p>
                @if ($runMeta['repo_url'])
                    <a
                        href="{{ $runMeta['repo_url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-0.5 block truncate font-mono text-sm text-cyan-400 transition-colors hover:text-cyan-300"
                    >
                        {{ $runMeta['repository'] }}
                    </a>
                @else
                    <p class="mt-0.5 truncate font-mono text-sm text-slate-400">{{ $runMeta['repository'] }}</p>
                @endif
            </div>
        @endif

        @if ($runMeta['pr_number'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Pull Request</p>
                @if ($runMeta['pr_url'])
                    <a
                        href="{{ $runMeta['pr_url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-0.5 block text-sm text-cyan-400 transition-colors hover:text-cyan-300"
                    >
                        #{{ $runMeta['pr_number'] }}
                    </a>
                @else
                    <p class="mt-0.5 text-sm text-slate-300">#{{ $runMeta['pr_number'] }}</p>
                @endif
            </div>
        @elseif ($runMeta['branch'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Branch</p>
                <p class="mt-0.5 font-mono text-sm text-slate-300">{{ $runMeta['branch'] }}</p>
            </div>
        @endif

        @if ($runMeta['environment'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Environment</p>
                <p class="mt-0.5 text-sm text-slate-300">{{ $runMeta['environment'] }}</p>
            </div>
        @endif

        @if ($runMeta['tier'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Tier</p>
                <p class="mt-0.5 text-sm text-slate-300">{{ $runMeta['tier'] }}</p>
            </div>
        @endif

        @if ($runMeta['actor'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Actor</p>
                <p class="mt-0.5 text-sm text-slate-300">{{ $runMeta['actor'] }}</p>
            </div>
        @endif

        @if ($runMeta['ingested_at'])
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Processed at</p>
                <p class="mt-0.5 font-mono text-sm text-slate-400">{{ $runMeta['ingested_at'] }}</p>
            </div>
        @endif

    </div>
</div>

{{-- Resolved since last run --}}
@if (count($fixedFindings) > 0)
    <div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
            <div class="flex items-center gap-2">
                <svg
                    class="size-3.5 shrink-0 text-green-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                <h3 class="text-sm font-semibold text-slate-200">Resolved</h3>
            </div>
            <span class="text-xs font-semibold text-green-400">{{ count($fixedFindings) }}</span>
        </div>
        <div class="divide-y divide-slate-800/60">
            @foreach ($fixedFindings as $finding)
                <div class="flex items-start gap-2.5 px-4 py-2.5">
                    <span
                        class="{{ $finding['sev_bg'] }} {{ $finding['sev_text'] }} mt-0.5 inline-flex shrink-0 items-center rounded px-1 py-0.5 text-xs font-bold ring-1"
                    >
                        {{ substr($finding['severity'], 0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-xs leading-snug text-slate-500">{{ $finding['title'] }}</p>
                        @if ($finding['package_name'])
                            <p class="truncate font-mono text-xs text-slate-700">{{ $finding['package_name'] }}</p>
                        @elseif ($finding['file_path'])
                            <p class="truncate font-mono text-xs text-slate-700">{{ $finding['file_path'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
