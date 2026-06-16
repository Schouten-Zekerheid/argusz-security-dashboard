<div class="space-y-6">

    @include('livewire.service-detail._header', [
        'service' => $this->service,
        'stats' => $this->stats,
    ])

    @include('livewire.service-detail._stat-cards', [
        'stats' => $this->stats,
        'serviceId' => $this->serviceId,
        'source' => $this->type === 'azure' ? 'azure' : 'github',
        'severityBreakdown' => $this->severityBreakdown,
    ])

    <div class="grid grid-cols-3 gap-6">

        {{-- Left: Tool sections --}}
        <div class="col-span-2 space-y-3">
            @forelse ($this->toolSections as $section)
                <div class="{{ $section['border_class'] }} shadow-xs overflow-hidden rounded-xl border bg-slate-900">

                    {{-- Section header --}}
                    <div class="flex items-center justify-between border-b border-slate-800 px-5 py-3">
                        <div class="flex items-center gap-3">
                            {{-- Status icon --}}
                            @if ($section['not_ran'])
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-700/60"
                                >
                                    <svg
                                        class="size-4 text-slate-500"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"
                                        />
                                    </svg>
                                </span>
                            @elseif ($section['has_issues'])
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-red-500/20"
                                >
                                    <svg
                                        class="size-4 text-red-400"
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
                                </span>
                            @else
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-green-500/20"
                                >
                                    <svg
                                        class="size-4 text-green-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m5 13 4 4L19 7"
                                        />
                                    </svg>
                                </span>
                            @endif

                            <p class="text-sm font-semibold text-slate-100">{{ $section['tool_label'] }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($section['not_ran'])
                                <span class="text-xs text-slate-500">Not executed</span>
                            @elseif ($section['has_issues'])
                                <span class="text-sm font-semibold text-red-400">{{ $section['finding_count'] }}
                                    finding{{ $section['finding_count'] !== 1 ? 's' : '' }} found</span>
                            @else
                                <span class="text-sm font-semibold text-green-400">No issues found</span>
                            @endif
                            <span
                                class="{{ $section['tool_badge_class'] }} inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                            >{{ $section['category'] }}</span>
                        </div>
                    </div>

                    {{-- Findings list --}}
                    @if ($section['has_issues'])
                        <div class="divide-y divide-slate-800/60">
                            @foreach ($section['findings']->take(5) as $finding)
                                <a
                                    wire:navigate
                                    href="{{ route('findings.show', $finding['id']) }}"
                                    class="flex items-start justify-between gap-4 px-5 py-2 transition-colors hover:bg-slate-800/30"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span
                                            class="{{ $finding['sev_dot'] }} mt-1.5 size-2 shrink-0 rounded-full"></span>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm text-slate-200"
                                                title="{{ $finding['title'] }}"
                                            >{{ $finding['title'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        @if ($finding['reference_id'])
                                            <span
                                                class="font-mono text-xs text-slate-500">{{ $finding['reference_id'] }}</span>
                                        @endif
                                        <span
                                            class="{{ $finding['sev_text'] }} text-xs font-semibold">{{ $finding['severity'] }}</span>
                                    </div>
                                </a>
                            @endforeach

                            @if ($section['finding_count'] > 5)
                                <a
                                    wire:navigate
                                    href="{{ route('findings', ['service' => $this->serviceId, 'tool' => $section['tool_key'], 'source' => $this->type === 'azure' ? 'azure' : 'github']) }}"
                                    class="block px-5 py-3 text-xs text-slate-500 transition-colors hover:bg-slate-800/30 hover:text-slate-300"
                                >
                                    + {{ $section['finding_count'] - 5 }} more findings →
                                </a>
                            @endif
                        </div>
                    @elseif (!$section['not_ran'])
                        <div class="px-5 py-4 text-sm text-slate-500">All checks passed. No issues found.
                        </div>
                    @else
                        <div class="px-5 py-4 text-sm text-slate-500">This tool was not executed in the last
                            pipeline.</div>
                    @endif

                </div>
            @empty
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-12 text-center">
                    <p class="text-sm text-slate-500">No scan data available yet.</p>
                </div>
            @endforelse
        </div>

        {{-- Right sidebar --}}
        <div class="space-y-4">
            @include('livewire.service-detail._scan-history', [
                'scanHistory' => $this->scanHistory,
                'serviceId' => $this->serviceId,
            ])
        </div>

    </div>

</div>
