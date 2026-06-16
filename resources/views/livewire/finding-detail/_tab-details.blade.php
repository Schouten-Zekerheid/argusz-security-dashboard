@props([
    'statusRaw',
    'findingStatus',
    'findingDetails',
    'packageInfoRows',
    'service',
    'githubUrl',
    'snippetViewData',
    'findingDetailRows',
])

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Left: metadata --}}
    <div class="space-y-6">

        {{-- Finding metadata grid --}}
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
            <div class="border-b border-slate-800 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-200">Finding Details</h2>
            </div>
            <dl class="divide-y divide-slate-800">
                @foreach ($findingDetailRows as $item)
                    <div class="flex items-start gap-3 px-5 py-3">
                        <dt class="w-32 shrink-0 text-xs font-medium uppercase tracking-wider text-slate-500">
                            {{ $item['label'] }}</dt>
                        <dd class="break-all font-mono text-sm text-slate-300">{{ $item['value'] }}</dd>
                    </div>
                @endforeach

                @if ($statusRaw === 'SNOOZED')
                    <div class="flex items-start gap-3 px-5 py-3">
                        <dt class="w-32 shrink-0 text-xs font-medium uppercase tracking-wider text-slate-500">
                            Status</dt>
                        <dd class="font-mono text-sm text-violet-300">Snoozed</dd>
                    </div>
                    @if ($findingStatus->snooze_reason)
                        <div class="flex items-start gap-3 px-5 py-3">
                            <dt class="w-32 shrink-0 text-xs font-medium uppercase tracking-wider text-slate-500">
                                Reason</dt>
                            <dd class="text-sm text-slate-300">{{ $findingStatus->snooze_reason }}</dd>
                        </div>
                    @endif
                @endif
            </dl>
        </div>

        {{-- Package info (SCA) --}}
        @if (!empty($findingDetails['details']['package_name']))
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
                <div class="border-b border-slate-800 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-200">Package Information</h2>
                </div>
                <dl class="divide-y divide-slate-800">
                    @foreach ($packageInfoRows as $item)
                        @if ($item['value'])
                            <div class="flex items-center gap-3 px-5 py-3">
                                <dt class="w-40 shrink-0 text-xs font-medium uppercase tracking-wider text-slate-500">
                                    {{ $item['label'] }}</dt>
                                <dd class="font-mono text-sm text-slate-300">{{ $item['value'] }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        @endif

        {{-- Service link --}}
        @if ($service)
            <div class="space-y-2 rounded-xl border border-slate-800 bg-slate-900 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-200">Service</h2>
                <p class="text-sm text-slate-300">{{ $service->name }}</p>
                @if ($service->repository_url)
                    <a
                        href="{{ $service->repository_url }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-xs text-indigo-400 transition-colors hover:text-indigo-300"
                    >
                        <x-icon.external-link class="size-3.5" />
                        View Repository
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Right: full finding details from pipeline run --}}
    <div class="space-y-6 lg:col-span-2">

        @if (!empty($findingDetails['description']))
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
                <div class="border-b border-slate-800 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-200">Description</h2>
                </div>
                <div class="prose prose-sm prose-invert max-w-none px-5 py-4 text-sm leading-relaxed text-slate-300">
                    {!! new \League\CommonMark\CommonMarkConverter([
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                        'open_links_in_new_window' => true,
                    ])->convert($findingDetails['description']) !!}
                </div>
            </div>
        @endif

        {{-- File / location (SAST) --}}
        @if (!empty($findingDetails['details']['file_path']))
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
                <div class="flex items-center justify-between gap-4 border-b border-slate-800 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-200">Location</h2>
                </div>
                <div class="space-y-2 px-5 py-4">
                    <div class="flex items-center gap-2 font-mono text-sm text-slate-300">
                        <x-icon.file class="size-4 shrink-0 text-slate-500" />
                        @if ($githubUrl)
                            <a
                                href="{{ $githubUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="break-all transition-colors hover:text-blue-400 hover:underline"
                            >{{ $findingDetails['details']['file_path'] }}</a>
                        @else
                            <span class="break-all">{{ $findingDetails['details']['file_path'] }}</span>
                        @endif
                        @if (!empty($findingDetails['details']['line_start']))
                            <span
                                class="text-slate-500">:{{ $findingDetails['details']['line_start'] }}{{ !empty($findingDetails['details']['line_end']) && $findingDetails['details']['line_end'] !== $findingDetails['details']['line_start'] ? '–' . $findingDetails['details']['line_end'] : '' }}</span>
                        @endif
                    </div>
                </div>

                {{-- Code snippet --}}
                @if ($snippetViewData)
                    <div
                        class="overflow-x-auto border-t border-slate-800"
                        x-data="{
                            lang: '{{ $snippetViewData['language'] }}',
                            firstLine: {{ $snippetViewData['firstLineNumber'] }},
                            highlighted: {{ json_encode($snippetViewData['highlightedNumbers']) }},
                            raw: {{ json_encode($snippetViewData['rawLines']) }},
                            rows: [],
                            init() {
                                const code = this.raw.join('\n');
                                let htmlLines;
                                try {
                                    const result = window.hljs?.highlight(code, { language: this.lang, ignoreIllegals: true });
                                    htmlLines = (result?.value ?? code).split('\n');
                                } catch (e) {
                                    htmlLines = code.split('\n').map(l => l.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'));
                                }
                                this.rows = htmlLines.map((html, i) => ({
                                    number: this.firstLine + i,
                                    html,
                                    highlighted: this.highlighted.includes(this.firstLine + i),
                                }));
                            }
                        }"
                    >
                        <table class="w-full font-mono text-xs">
                            <template
                                x-for="line in rows"
                                :key="line.number"
                            >
                                <tr :class="line.highlighted ? 'bg-amber-500/10' : ''">
                                    <td
                                        class="w-12 select-none border-r px-3 py-0.5 text-right"
                                        :class="line.highlighted ? 'text-amber-500/60 border-amber-500/20' :
                                            'text-slate-700 border-slate-800'"
                                        x-text="line.number"
                                    ></td>
                                    <td
                                        class="whitespace-pre px-4 py-0.5"
                                        :class="line.highlighted ? 'text-slate-200' : ''"
                                        x-html="line.html"
                                    >
                                    </td>
                                </tr>
                            </template>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        {{-- Fallback: nothing from pipeline run --}}
        @if (empty($findingDetails))
            <div class="rounded-xl border border-slate-800 bg-slate-900 px-5 py-8 text-center text-sm text-slate-500">
                No additional details available for this finding.
            </div>
        @endif
    </div>
</div>
