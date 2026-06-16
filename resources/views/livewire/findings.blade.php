<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-100">All findings</h1>
        <p class="mt-1 text-sm text-slate-400">Complete overview of findings across all services.</p>
    </div>

    <div class="flex items-center gap-3">
        <button
            wire:click="toggleFilterValue('statusFilter', 'snoozed')"
            type="button"
            @class([
                'inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500/30',
                'border-violet-500/50 bg-violet-500/15 text-violet-300 hover:bg-violet-500/25' => in_array(
                    'snoozed',
                    $this->selectedFilterValues('statusFilter'),
                    true),
                'border-slate-700 bg-slate-900 text-slate-400 hover:border-slate-600 hover:text-slate-200' => !in_array(
                    'snoozed',
                    $this->selectedFilterValues('statusFilter'),
                    true),
            ])
        >
            <x-icon.snooze class="size-4" />
            Snoozed findings
        </button>
    </div>

    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900">
        <div class="border-b border-slate-800 bg-slate-950/40 px-6 py-4">
            @php
                $selectedServices = $this->selectedFilterValues('serviceFilter');
                $selectedTools = $this->selectedFilterValues('toolFilter');
                $selectedSeverities = $this->selectedFilterValues('severityFilter');
                $selectedStatuses = $this->selectedFilterValues('statusFilter');
            @endphp

            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div
                    x-data="{ open: false }"
                    class="relative space-y-1"
                >
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Service</span>
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-left text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <span>{{ count($selectedServices) === 0 ? 'All services' : count($selectedServices) . ' selected' }}</span>
                        <span class="text-slate-400">↓</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-on:click.outside="open = false"
                        class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-700 bg-slate-950 p-1 shadow-xl"
                    >
                        <button
                            wire:click="clearFilter('serviceFilter')"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                        >
                            <span @class([
                                'grid size-4 place-items-center rounded border',
                                'border-slate-600' => $selectedServices !== [],
                                'border-slate-300 bg-slate-200 text-slate-950' => $selectedServices === [],
                            ])>
                                @if ($selectedServices === [])
                                    <svg
                                        class="size-3"
                                        viewBox="0 0 12 12"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M2 6.5 4.5 9 10 3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                @endif
                            </span>
                            All services
                        </button>
                        @foreach ($this->serviceOptions as $service)
                            <button
                                wire:click="toggleFilterValue('serviceFilter', @js($service['id']))"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                            >
                                <span @class([
                                    'grid size-4 place-items-center rounded border',
                                    'border-slate-500' => !in_array($service['id'], $selectedServices, true),
                                    'border-slate-300 bg-slate-200 text-slate-950' => in_array(
                                        $service['id'],
                                        $selectedServices,
                                        true),
                                ])>
                                    @if (in_array($service['id'], $selectedServices, true))
                                        <svg
                                            class="size-3"
                                            viewBox="0 0 12 12"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M2 6.5 4.5 9 10 3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    @endif
                                </span>
                                {{ $service['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div
                    x-data="{ open: false }"
                    class="relative space-y-1"
                >
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Tool</span>
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-left text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <span>{{ count($selectedTools) === 0 ? 'All tools' : count($selectedTools) . ' selected' }}</span>
                        <span class="text-slate-400">↓</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-on:click.outside="open = false"
                        class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-700 bg-slate-950 p-1 shadow-xl"
                    >
                        <button
                            wire:click="clearFilter('toolFilter')"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                        >
                            <span @class([
                                'grid size-4 place-items-center rounded border',
                                'border-slate-600' => $selectedTools !== [],
                                'border-slate-300 bg-slate-200 text-slate-950' => $selectedTools === [],
                            ])>
                                @if ($selectedTools === [])
                                    <svg
                                        class="size-3"
                                        viewBox="0 0 12 12"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M2 6.5 4.5 9 10 3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                @endif
                            </span>
                            All tools
                        </button>
                        @foreach ($this->toolOptions as $tool)
                            <button
                                wire:click="toggleFilterValue('toolFilter', @js($tool))"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                            >
                                <span @class([
                                    'grid size-4 place-items-center rounded border',
                                    'border-slate-500' => !in_array($tool, $selectedTools, true),
                                    'border-slate-300 bg-slate-200 text-slate-950' => in_array(
                                        $tool,
                                        $selectedTools,
                                        true),
                                ])>
                                    @if (in_array($tool, $selectedTools, true))
                                        <svg
                                            class="size-3"
                                            viewBox="0 0 12 12"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M2 6.5 4.5 9 10 3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    @endif
                                </span>
                                {{ $tool }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div
                    x-data="{ open: false }"
                    class="relative space-y-1"
                >
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Severity</span>
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-left text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <span>{{ count($selectedSeverities) === 0 ? 'All severity levels' : count($selectedSeverities) . ' selected' }}</span>
                        <span class="text-slate-400">↓</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-on:click.outside="open = false"
                        class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-700 bg-slate-950 p-1 shadow-xl"
                    >
                        <button
                            wire:click="clearFilter('severityFilter')"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                        >
                            <span @class([
                                'grid size-4 place-items-center rounded border',
                                'border-slate-600' => $selectedSeverities !== [],
                                'border-slate-300 bg-slate-200 text-slate-950' =>
                                    $selectedSeverities === [],
                            ])>
                                @if ($selectedSeverities === [])
                                    <svg
                                        class="size-3"
                                        viewBox="0 0 12 12"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M2 6.5 4.5 9 10 3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                @endif
                            </span>
                            All severity levels
                        </button>
                        @foreach ($this->severityOptions as $severity)
                            <button
                                wire:click="toggleFilterValue('severityFilter', @js($severity))"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                            >
                                <span @class([
                                    'grid size-4 place-items-center rounded border',
                                    'border-slate-500' => !in_array($severity, $selectedSeverities, true),
                                    'border-slate-300 bg-slate-200 text-slate-950' => in_array(
                                        $severity,
                                        $selectedSeverities,
                                        true),
                                ])>
                                    @if (in_array($severity, $selectedSeverities, true))
                                        <svg
                                            class="size-3"
                                            viewBox="0 0 12 12"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M2 6.5 4.5 9 10 3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    @endif
                                </span>
                                {{ $severity }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div
                    x-data="{ open: false }"
                    class="relative space-y-1"
                >
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Status</span>
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-left text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <span>{{ count($selectedStatuses) === 0 ? 'All statuses' : count($selectedStatuses) . ' selected' }}</span>
                        <span class="text-slate-400">↓</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        x-on:click.outside="open = false"
                        class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-700 bg-slate-950 p-1 shadow-xl"
                    >
                        <button
                            wire:click="clearFilter('statusFilter')"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                        >
                            <span @class([
                                'grid size-4 place-items-center rounded border',
                                'border-slate-600' => $selectedStatuses !== [],
                                'border-slate-300 bg-slate-200 text-slate-950' => $selectedStatuses === [],
                            ])>
                                @if ($selectedStatuses === [])
                                    <svg
                                        class="size-3"
                                        viewBox="0 0 12 12"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M2 6.5 4.5 9 10 3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                @endif
                            </span>
                            All statuses
                        </button>
                        @foreach ($this->statusOptions as $status)
                            <button
                                wire:click="toggleFilterValue('statusFilter', @js($status))"
                                type="button"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm text-slate-300 hover:bg-slate-800"
                            >
                                <span @class([
                                    'grid size-4 place-items-center rounded border',
                                    'border-slate-500' => !in_array($status, $selectedStatuses, true),
                                    'border-slate-300 bg-slate-200 text-slate-950' => in_array(
                                        $status,
                                        $selectedStatuses,
                                        true),
                                ])>
                                    @if (in_array($status, $selectedStatuses, true))
                                        <svg
                                            class="size-3"
                                            viewBox="0 0 12 12"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M2 6.5 4.5 9 10 3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    @endif
                                </span>
                                {{ $status }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <label class="space-y-1">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Source type</span>
                    <select
                        wire:model.live="scanSourceFilter"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <option value="">All source types</option>
                        <option value="github">Github</option>
                        <option value="azure">Azure/Containers</option>
                    </select>
                </label>

                @if ($serviceFilter || $toolFilter || $severityFilter || $statusFilter || $scanSourceFilter)
                    <div class="flex items-end">
                        <button
                            wire:click="resetFilters"
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-medium text-slate-200 transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                        >
                            Clear filters
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        @foreach ([['key' => 'service_name', 'label' => 'Service'], ['key' => 'title', 'label' => 'Title'], ['key' => 'severity', 'label' => 'Severity'], ['key' => 'type', 'label' => 'Type'], ['key' => 'status', 'label' => 'Status'], ['key' => 'reference_id', 'label' => 'Reference'], ['key' => 'status_updated_at', 'label' => 'Updated']] as $col)
                            <th class="text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                                <button
                                    wire:click="sort('{{ $col['key'] }}')"
                                    class="flex w-full cursor-pointer items-center gap-1 px-6 py-3 transition-colors hover:text-slate-200"
                                >
                                    {{ $col['label'] }}
                                    @if (isset($this->sortColumnMap[$col['key']]))
                                        <span>{{ $this->sortColumnMap[$col['key']]['dir'] === 'asc' ? '↑' : '↓' }}</span>
                                        @if (count($sortColumns) > 1)
                                            <span
                                                class="text-[10px] text-slate-500">{{ $this->sortColumnMap[$col['key']]['position'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-600">↕</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($this->paginatedFindings as $finding)
                        <tr
                            wire:key="{{ $finding['id'] }}"
                            onclick="window.location='{{ route('findings.show', $finding['id']) }}'"
                            class="cursor-pointer transition-colors hover:bg-slate-800/60"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-100">{{ $finding['service_name'] }}</p>
                            </td>

                            <td class="px-6 py-4 tracking-wide text-slate-300">{{ $finding['title'] }}</td>

                            <td class="px-6 py-4">
                                <span
                                    class="{{ $finding['severity_badge'] }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                >
                                    {{ strtoupper($finding['severity']) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-slate-300">{{ $finding['type'] }}</td>

                            <td class="px-6 py-4">
                                <span
                                    class="{{ $finding['status_badge'] }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                >
                                    {{ strtoupper($finding['status']) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-slate-400">
                                @if (!empty($finding['reference_id']))
                                    <span class="font-mono text-xs">{{ $finding['reference_id'] }}</span>
                                @else
                                    <span class="text-slate-500">-</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-slate-400">
                                @if ($finding['status_updated_at'])
                                    {{ \Carbon\Carbon::parse($finding['status_updated_at'])->diffForHumans() }}
                                @else
                                    <span class="text-slate-500">Unknown</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-sm text-slate-500"
                            >
                                No findings found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination
            :paginator="$this->paginatedFindings"
            label="findings"
        />
    </div>

</div>
