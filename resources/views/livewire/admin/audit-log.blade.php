<div class="space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">Audit Log</h1>
            <p class="mt-1 text-sm text-slate-400">Overview of all actions performed by users.</p>
        </div>

        <a
            href="{{ route(
                'admin.audit-log.export',
                array_filter([
                    'log' => $filterLog,
                    'event' => $filterEvent,
                    'actor' => $filterActor,
                    'from' => $filterFrom,
                    'to' => $filterTo,
                ]),
            ) }}"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-green-700/50 bg-green-500/10 px-4 py-2.5 text-sm font-medium text-green-300 transition-colors hover:bg-green-500/20 focus:outline-none focus:ring-2 focus:ring-green-500/30"
        >
            <img
                src="{{ asset('excel-icon.svg') }}"
                class="size-4"
            >
            Export to XLSX
        </a>
    </div>

    <div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">

        {{-- Filters --}}
        <div class="border-b border-slate-800 bg-slate-950/40 px-6 py-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <label class="space-y-1">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Log</span>
                    <select
                        wire:model.live="filterLog"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <option value="">All logs</option>
                        @foreach ($this->logNames as $logName)
                            <option value="{{ $logName }}">{{ $logName }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Event</span>
                    <select
                        wire:model.live="filterEvent"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <option value="">All events</option>
                        @foreach ($this->eventNames as $eventName)
                            <option value="{{ $eventName }}">{{ $eventName }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Actor</span>
                    <select
                        wire:model.live="filterActor"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                        <option value="">All users</option>
                        @foreach ($this->uniqueActors as $actor)
                            <option value="{{ $actor->id }}">{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">From</span>
                    <input
                        wire:model.live="filterFrom"
                        type="date"
                        style="color-scheme: dark"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                    >
                </label>

                <div class="space-y-1">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">To</span>
                    <div class="flex items-center gap-2">
                        <input
                            wire:model.live="filterTo"
                            type="date"
                            style="color-scheme: dark"
                            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                        >
                        @if ($filterLog || $filterActor || $filterFrom || $filterTo || $filterEvent)
                            <button
                                wire:click="resetFilters"
                                type="button"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-medium text-slate-200 transition-colors hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30"
                            >
                                Reset
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th
                            class="w-36 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                            Timestamp (UTC)</th>
                        <th
                            class="w-20 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                            Log
                        </th>
                        <th
                            class="w-36 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                            Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                            Description</th>
                        <th
                            class="w-40 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                            Actor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">
                            Details
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($this->logs as $entry)
                        <tr class="align-top transition-colors hover:bg-slate-800/40">
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-500">
                                {{ $entry->created_at->format('d-m-Y H:i') }} UTC
                            </td>
                            <td class="px-6 py-4">
                                @if ($entry->log_name === 'auth')
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-500/20 px-2.5 py-0.5 text-xs font-medium text-blue-300 ring-1 ring-blue-400/30"
                                    >
                                        auth
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-purple-500/20 px-2.5 py-0.5 text-xs font-medium text-purple-300 ring-1 ring-purple-400/30"
                                    >
                                        {{ $entry->log_name }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                {{ $entry->event }}
                            </td>
                            <td class="px-6 py-4 text-slate-200">
                                <div>{{ $entry->description }}</div>
                                @if ($findingId = $entry->properties->get('finding_id'))
                                    <div class="mt-1 text-xs">
                                        @if ($finding = $this->findings->get($findingId))
                                            <span class="text-slate-400">Finding:</span>
                                            <a
                                                wire:navigate
                                                href="{{ route('findings.show', $findingId) }}"
                                                class="font-medium text-cyan-400 transition-colors hover:text-cyan-300 hover:underline"
                                            >
                                                {{ $finding->title }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">Finding ID:</span>
                                            <span class="font-mono text-slate-500">{{ $findingId }}</span>
                                            <span class="text-slate-500">(not found)</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($entry->causer)
                                    <span class="text-slate-300">{{ $entry->causer->name }}</span>
                                @elseif ($entry->properties->get('email'))
                                    <span
                                        class="text-xs italic text-slate-500">{{ $entry->properties->get('email') }}</span>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($entry->properties->isNotEmpty())
                                    <dl class="space-y-0.5">
                                        @foreach ($entry->properties as $key => $value)
                                            <div class="flex gap-1.5 text-xs">
                                                <dt class="shrink-0 text-slate-500">{{ $key }}:</dt>
                                                <dd class="font-medium text-slate-300">
                                                    @if (is_array($value))
                                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-6 py-12 text-center text-sm text-slate-500"
                            >
                                No log entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination
            :paginator="$this->logs"
            label="logs"
        />
    </div>
