<div class="space-y-6">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-100">Project security overview</h1>
        <p class="mt-1 text-sm text-slate-400">
            Overview of security findings per repository.
        </p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Open findings --}}
        <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Open findings</p>
            <p class="mt-1 text-3xl font-bold text-slate-100">{{ $this->stats['open_findings'] }}</p>
            @if ($this->stats['critical_issues'] > 0)
                <p class="text-severity-critical mt-2 flex items-center gap-1 text-sm font-medium">
                    <svg
                        class="size-4 shrink-0"
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
                    {{ $this->stats['critical_issues'] }} critical issues
                </p>
            @else
                <p class="mt-2 text-sm font-medium text-green-400">No critical issues</p>
            @endif

            <p class="mt-2 text-sm text-slate-500">Total across all projects</p>
        </div>

        {{-- Projects per status --}}
        <div class="shadow-xs col-span-2 rounded-xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Status distribution</p>
            <div class="mt-3 flex h-2.5 w-full overflow-hidden rounded-full bg-slate-800">
                @foreach ($this->statusBreakdown as $status)
                    @if ($status['count'] > 0)
                        <div
                            class="{{ $status['color'] }}"
                            style="flex: {{ $status['count'] }} 0 0"
                        ></div>
                    @endif
                @endforeach
            </div>
            <div class="mt-2.5 flex w-full">
                @foreach ($this->statusBreakdown as $status)
                    @if ($status['count'] > 0)
                        <div
                            class="flex flex-col items-center"
                            style="flex: {{ $status['count'] }} 0 0"
                        >
                            <p class="{{ $status['text'] }} text-xs font-bold">{{ $status['count'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $status['label'] }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
            @if (collect($this->statusBreakdown)->contains('count', 0))
                <div class="mt-1.5 flex gap-3">
                    @foreach ($this->statusBreakdown as $status)
                        @if ($status['count'] === 0)
                            <span class="text-xs text-slate-600">{{ $status['label'] }}: 0</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    {{-- Tabs + table --}}
    <div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

        {{-- Tabs --}}
        <div class="flex items-center justify-between border-b border-slate-800 px-4">
            <nav
                class="-mb-px flex gap-6"
                aria-label="Tabs"
            >
                @foreach ([
        'all' => 'All services',
        'critical' => 'Critical',
        'warning' => 'Warning',
        'healthy' => 'Healthy',
    ] as $tab => $label)
                    <button
                        wire:click="activateTab('{{ $tab }}')"
                        wire:loading.attr="disabled"
                        wire:target="activateTab('{{ $tab }}')"
                        @class([
                            'relative flex items-center gap-2 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap cursor-pointer disabled:cursor-default',
                            'border-cyan-400 text-cyan-300' => $activeTab === $tab,
                            'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-600' =>
                                $activeTab !== $tab,
                        ])
                    >

                        {{-- Spinner overlay (visible while loading this tab) --}}
                        <svg
                            wire:loading
                            wire:target="activateTab('{{ $tab }}')"
                            class="absolute left-1/2 top-1/2 size-3.5 -translate-x-1/2 -translate-y-1/2 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                        </svg>

                        <span
                            wire:loading.class="opacity-20"
                            wire:target="activateTab('{{ $tab }}')"
                        >{{ $label }}</span>

                        <span
                            wire:loading.class="opacity-20"
                            wire:target="activateTab('{{ $tab }}')"
                            @class([
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-cyan-500/20 text-cyan-300' => $activeTab === $tab,
                                'bg-slate-800 text-slate-400' => $activeTab !== $tab,
                            ])
                        >
                            {{ $this->tabCounts[$tab] }}
                        </span>

                    </button>
                @endforeach
            </nav>
            <div class="flex items-center gap-2 py-2">
                <select
                    wire:model.live="scanTypeFilter"
                    class="cursor-pointer rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                >
                    <option value="all">Combined</option>
                    <option value="github">Github</option>
                    <option value="azure">Containers/Azure</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <table class="min-w-full divide-y divide-slate-800 text-sm">
            <thead class="bg-slate-950/60">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Service
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Environment
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Findings
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Last scan
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($this->services as $service)
                    <tr @class([
                        'hover:bg-slate-800/60 transition-colors',
                        'border-l-2 border-l-red-400/80 bg-red-500/5' =>
                            $service['status'] === 'critical',
                    ])>

                        {{-- Project name + repo --}}
                        <td class="px-6 py-4">
                            <a
                                wire:navigate
                                href="{{ route('services.show', ['id' => $service['id'], 'type' => $service['type']]) }}"
                                class="group flex w-fit items-center gap-2"
                            >
                                @if (
                                    $service['type'] === 'github' &&
                                        config('integrations.scm.provider') === 'github' &&
                                        str_contains($service['repo_url'] ?? '', 'github.com'))
                                    <svg
                                        class="size-4 shrink-0 text-slate-300"
                                        viewBox="0 0 98 96"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        aria-label="GitHub"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            clip-rule="evenodd"
                                            d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"
                                        />
                                    </svg>
                                @else
                                    {{-- Microsoft Azure Icon --}}
                                    <svg
                                        class="size-4 shrink-0 text-cyan-400"
                                        viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        aria-label="Azure"
                                    >
                                        <path
                                            d="M22.379 23.343a1.62 1.62 0 0 0 1.536-2.14v.002L17.35 1.76A1.62 1.62 0 0 0 15.816.657H8.184A1.62 1.62 0 0 0 6.65 1.76L.086 21.204a1.62 1.62 0 0 0 1.536 2.139h4.741a1.62 1.62 0 0 0 1.535-1.103l.977-2.892 4.947 3.675c.28.208.618.32.966.32m-3.084-12.531 3.624 10.739a.54.54 0 0 1-.51.713v-.001h-.03a.54.54 0 0 1-.322-.106l-9.287-6.9h4.853m6.313 7.006c.116-.326.13-.694.007-1.058L9.79 1.76a1.722 1.722 0 0 0-.007-.02h6.034a.54.54 0 0 1 .512.366l6.562 19.445a.54.54 0 0 1-.338.684"
                                        />
                                    </svg>
                                @endif
                                <span
                                    class="font-medium text-slate-100 transition-colors group-hover:text-cyan-300">{{ $service['name'] }}</span>
                            </a>
                        </td>

                        {{-- Environment --}}
                        <td class="px-6 py-4 text-slate-400">
                            {{ $service['environment'] ?? '—' }}
                        </td>

                        {{-- StatusBadge: `status` attribute → App\View\Components\StatusBadge::$status --}}
                        <td class="px-6 py-4">
                            <x-status-badge :status="$service['status']" />
                        </td>

                        {{-- Findings --}}
                        <td class="px-6 py-4">
                            @if ($service['no_default_branch_scan'] ?? false)
                                <p class="text-slate-500">Not scanned yet</p>
                            @elseif ($service['open_count'] > 0)
                                <a
                                    wire:navigate
                                    href="{{ route('findings', ['service' => $service['id'], 'status' => 'open,returning', 'source' => $service['type']]) }}"
                                    class="font-medium text-slate-100 transition-colors hover:text-cyan-300"
                                >
                                    {{ $service['open_count'] }} open findings
                                </a>
                                @if ($service['critical_count'] > 0)
                                    <a
                                        wire:navigate
                                        href="{{ route('findings', ['service' => $service['id'], 'severity' => 'critical', 'status' => 'open,returning', 'source' => $service['type']]) }}"
                                        class="text-severity-critical mt-0.5 block text-xs transition-colors hover:text-red-300"
                                    >
                                        {{ $service['critical_count'] }} critical
                                    </a>
                                @endif
                            @else
                                <p class="text-slate-500">No issues</p>
                            @endif
                        </td>

                        {{-- Last scan --}}
                        <td class="px-6 py-4 text-slate-400">
                            @if ($service['last_run_at'] && $service['last_run_id'])
                                <a
                                    wire:navigate
                                    href="{{ route('pipeline-runs.show', ['serviceId' => $service['id'], 'runId' => $service['last_run_id']]) }}"
                                    class="transition-colors hover:text-cyan-300"
                                >
                                    {{ \Carbon\Carbon::parse($service['last_run_at'])->diffForHumans() }}
                                </a>
                            @elseif ($service['last_run_at'])
                                {{ \Carbon\Carbon::parse($service['last_run_at'])->diffForHumans() }}
                            @else
                                <span class="text-slate-500">Never</span>
                            @endif
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="5"
                            class="px-6 py-12 text-center text-sm text-slate-500"
                        >
                            No projects found for this filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Footer --}}
        @if ($this->services->isNotEmpty())
            <div class="border-t border-slate-800 bg-slate-950/40 px-6 py-3">
                <p class="text-xs text-slate-500">
                    {{ $this->services->count() }} of {{ $this->tabCounts['all'] }} projects
                </p>
            </div>
        @endif

    </div>

</div>
