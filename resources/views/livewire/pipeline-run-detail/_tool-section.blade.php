@props(['section'])

<div class="{{ $section['border_class'] }} shadow-xs overflow-hidden rounded-xl border bg-slate-900">

    {{-- Tool header --}}
    <div class="flex items-center justify-between gap-3 px-5 py-4">
        <div class="flex min-w-0 items-center gap-3">

            {{-- Status icon --}}
            @if ($section['not_ran'])
                <div
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-800 ring-1 ring-slate-700">
                    <svg
                        class="size-3.5 text-slate-500"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 12H6"
                        />
                    </svg>
                </div>
            @elseif ($section['has_issues'])
                <div
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-amber-500/15 ring-1 ring-amber-400/40">
                    <svg
                        class="size-3.5 text-amber-400"
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
                </div>
            @else
                <div
                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-green-500/15 ring-1 ring-green-400/40">
                    <svg
                        class="size-3.5 text-green-400"
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
                </div>
            @endif

            {{-- Tool name + badge --}}
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-200">{{ $section['tool_label'] }}</span>
                    <span
                        class="{{ $section['tool_badge_class'] }} inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    >
                        {{ $section['category'] ?: $section['tool_key'] }}
                    </span>
                    @if ($section['scan_type'])
                        <span class="font-mono text-xs text-slate-600">{{ $section['scan_type'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Finding count --}}
        <span
            class="{{ $section['has_issues'] ? 'text-amber-400' : ($section['not_ran'] ? 'text-slate-600' : 'text-green-400') }} shrink-0 text-sm font-semibold"
        >
            @if ($section['not_ran'])
                Not executed
            @elseif ($section['finding_count'] === 0)
                Clean
            @else
                {{ $section['finding_count'] }} finding{{ $section['finding_count'] === 1 ? '' : 's' }}
            @endif
        </span>
    </div>

    {{-- Severity pills --}}
    @if (!$section['not_ran'] && $section['has_issues'])
        <div class="flex flex-wrap items-center gap-2 px-5 pb-3">
            @foreach ($section['severity_counts'] as $sevKey => $count)
                @if ($count > 0)
                    @php($severity = \App\Enums\Severity::fromValue($sevKey))
                    <span
                        class="{{ $severity->panelClass() }} {{ $severity->textClass() }} inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1"
                    >
                        {{ $count }} {{ $severity->label() }}
                    </span>
                @endif
            @endforeach
        </div>

        {{-- Findings list --}}
        <div class="divide-y divide-slate-800/40 border-t border-slate-800/60">
            @foreach ($section['mapped_findings'] as $finding)
                <div
                    @if (filled($finding['finding_status_id'])) x-data
                        @click="Livewire.navigate('{{ route('findings.show', $finding['finding_status_id']) }}')"
                        class="{{ $finding['is_new'] ? 'bg-emerald-500/5 px-5' : 'px-5' }} py-3 flex items-start gap-3 cursor-pointer hover:bg-slate-800/60 transition-colors"
                    @else
                        class="{{ $finding['is_new'] ? 'bg-emerald-500/5 px-5' : 'px-5' }} py-3 flex items-start gap-3" @endif>

                    {{-- Severity badge --}}
                    <div class="mt-0.5 flex shrink-0 items-center gap-1.5">
                        <span
                            class="{{ $finding['sev_bg'] }} {{ $finding['sev_text'] }} inline-flex items-center rounded px-1.5 py-0.5 text-xs font-bold ring-1"
                        >
                            {{ substr($finding['severity'], 0, 1) }}
                        </span>
                        @if ($finding['is_new'])
                            <span
                                class="inline-flex items-center rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-emerald-400 ring-1 ring-emerald-500/20"
                            >
                                NEW
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        {{-- Title + reference_id --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-medium leading-snug text-slate-300">{{ $finding['title'] }}</p>
                            @if ($finding['reference_id'] && $finding['reference_id'] !== $finding['title'])
                                <span class="font-mono text-xs text-slate-600">{{ $finding['reference_id'] }}</span>
                            @endif
                        </div>

                        {{-- Package (Trivy) --}}
                        @if ($finding['package_name'])
                            <p class="mt-0.5 font-mono text-xs text-slate-500">
                                {{ $finding['package_name'] }}
                                @if ($finding['installed_version'])
                                    <span class="text-slate-600">{{ $finding['installed_version'] }}</span>
                                @endif
                                @if ($finding['fixed_version'])
                                    <span class="ml-1 font-bold text-slate-500">→ fixed in:
                                        {{ $finding['fixed_version'] }}</span>
                                @endif
                            </p>
                        @endif

                        {{-- File path + line --}}
                        @if ($finding['file_path'])
                            <p class="mt-0.5 truncate font-mono text-xs">
                                @if ($finding['github_url'])
                                    <a
                                        href="{{ $finding['github_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @click.stop
                                        class="text-slate-500 transition-colors hover:text-blue-400 hover:underline"
                                    >
                                        {{ $finding['file_path'] }}
                                        @if ($finding['line_start'])
                                            <span
                                                class="text-slate-600 hover:text-blue-400">:{{ $finding['line_start'] }}</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="text-slate-600">{{ $finding['file_path'] }}</span>
                                    @if ($finding['line_start'])
                                        <span class="text-slate-700">:{{ $finding['line_start'] }}</span>
                                    @endif
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Arrow indicator (row navigates when finding_status_id is known) --}}
                    @if (filled($finding['finding_status_id']))
                        <svg
                            class="mt-0.5 size-3.5 shrink-0 text-slate-700"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 18l6-6-6-6"
                            />
                        </svg>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif (!$section['not_ran'] && !$section['has_issues'])
        <div class="px-5 pb-4">
            <p class="text-xs text-slate-600">No issues found in this scan.</p>
        </div>
    @endif

</div>
