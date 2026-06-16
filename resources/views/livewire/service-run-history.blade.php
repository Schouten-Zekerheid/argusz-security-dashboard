<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a
            wire:navigate
            href="{{ route('dashboard') }}"
            class="transition-colors hover:text-slate-300"
        >Services</a>
        <span>/</span>
        <a
            wire:navigate
            href="{{ route('services.show', ['id' => $this->serviceId, 'type' => $this->type]) }}"
            class="transition-colors hover:text-slate-300"
        >{{ $this->service->name }}</a>
        <span>/</span>
        <span class="text-slate-300">Scan History</span>
    </nav>

    {{-- Page header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Scan History</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $this->service->name }}</p>
        </div>
        <a
            wire:navigate
            href="{{ route('services.show', ['id' => $this->serviceId, 'type' => $this->type]) }}"
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

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-3">

        {{-- Status filter --}}
        <div class="flex items-center overflow-hidden rounded-lg border border-slate-700 text-xs">
            @foreach (['' => 'All', 'issues' => 'Issues', 'clean' => 'Clean'] as $value => $label)
                <button
                    wire:click="$set('filterStatus', '{{ $value }}')"
                    class="{{ $filterStatus === $value ? 'bg-slate-700 text-slate-100' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' }} px-3 py-1.5 font-medium transition-colors"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Branch filter --}}
        <div class="relative">
            <input
                wire:model.live.debounce.300ms="filterBranch"
                type="text"
                placeholder="Filter by branch…"
                class="w-48 rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-300 placeholder-slate-600 transition-colors focus:border-slate-500 focus:outline-none"
            />
            @if ($filterBranch !== '')
                <button
                    wire:click="$set('filterBranch', '')"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-600 transition-colors hover:text-slate-400"
                >
                    <svg
                        class="size-3"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Result count --}}
        <span class="ml-auto text-xs text-slate-600">
            {{ $this->paginatedRuns->total() }} {{ $this->paginatedRuns->total() === 1 ? 'run' : 'runs' }}
        </span>
    </div>

    {{-- Runs table --}}
    <div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full divide-y divide-slate-800 text-sm">
            <thead class="bg-slate-950/60">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Actor
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Branch
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Commit
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Tools
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                        Findings
                    </th>
                    <th class="w-8 px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse ($this->paginatedRuns as $run)
                    <tr
                        x-data
                        @click="Livewire.navigate('{{ route('pipeline-runs.show', ['serviceId' => $this->serviceId, 'runId' => $run['run_id']]) }}')"
                        class="cursor-pointer transition-colors hover:bg-slate-800/60"
                    >

                        {{-- Date --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <p class="text-xs font-medium text-slate-300">{{ $run['ingested_at'] }}</p>
                            @if ($run['ingested_at_diff'])
                                <p class="mt-0.5 text-xs text-slate-600">{{ $run['ingested_at_diff'] }}</p>
                            @endif
                        </td>

                        {{-- Actor --}}
                        <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-400">
                            {{ $run['actor'] ?? '—' }}
                        </td>

                        {{-- Branch --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            @if ($run['branch'] && $run['repository_url'])
                                <a
                                    href="{{ $run['repository_url'] }}/tree/{{ $run['branch'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    @click.stop
                                    class="font-mono text-xs text-slate-400 transition-colors hover:text-slate-200"
                                >{{ $run['branch'] }}</a>
                            @elseif ($run['branch'])
                                <span class="font-mono text-xs text-slate-400">{{ $run['branch'] }}</span>
                            @else
                                <span class="text-slate-700">—</span>
                            @endif
                        </td>

                        {{-- Commit --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            @if ($run['commit_short'] && $run['repository_url'] && $run['commit_hash'])
                                <a
                                    href="{{ $run['repository_url'] }}/commit/{{ $run['commit_hash'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    @click.stop
                                    class="font-mono text-xs text-slate-600 transition-colors hover:text-slate-400"
                                >{{ $run['commit_short'] }}</a>
                            @elseif ($run['commit_short'])
                                <span class="font-mono text-xs text-slate-600">{{ $run['commit_short'] }}</span>
                            @else
                                <span class="text-slate-700">—</span>
                            @endif
                        </td>

                        {{-- Tool statuses --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-2">
                                @foreach (['SCA', 'SAST', 'SECRETS', 'IaC'] as $tool)
                                    <div class="flex flex-col items-center gap-0.5">
                                        @if (($run['tool_statuses'][$tool] ?? null) === 'success')
                                            <svg
                                                class="size-3.5 text-green-500"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        @elseif (($run['tool_statuses'][$tool] ?? null) === 'missing')
                                            <svg
                                                class="size-3.5 text-slate-700"
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
                                        @else
                                            <span class="block size-3.5"></span>
                                        @endif
                                        <span class="text-[9px] font-medium text-slate-700">{{ $tool }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </td>

                        {{-- Findings --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="{{ $run['status_class'] }} text-xs font-semibold">
                                {{ $run['status_text'] }}
                            </span>
                        </td>

                        {{-- Arrow --}}
                        <td class="py-4 pr-4 text-right">
                            <svg
                                class="size-4 text-slate-700 transition-colors group-hover:text-slate-400"
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
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="7"
                            class="px-6 py-12 text-center text-sm text-slate-500"
                        >
                            No runs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if ($this->paginatedRuns->hasPages())
            <div class="border-t border-slate-800 px-6 py-4">
                <x-pagination
                    :paginator="$this->paginatedRuns"
                    label="runs"
                />
            </div>
        @endif
    </div>

</div>
