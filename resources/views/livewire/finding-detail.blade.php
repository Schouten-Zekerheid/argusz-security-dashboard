<div>
    <div class="mb-6 space-y-3">
        <x-flash-messages />
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-100">
                {{ $this->findingDetails['title'] ?? $this->findingStatus->title }}</h1>
            <div class="mt-1 flex items-center gap-2">
                <x-status-badge :status="$this->statusRaw" />
                <span class="text-xs text-slate-500">•</span>
                <span
                    class="{{ $this->severityBadge }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                >{{ $this->severityRaw }}</span>

                @if ($this->findingStatus->current_status === 'open')
                    @if ($this->isSlaBreached)
                        <span class="text-xs text-slate-500">•</span>
                        <span
                            class="inline-flex items-center rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-semibold text-red-400 ring-1 ring-red-500/30"
                        >
                            SLA Breached (limit: {{ $this->slaThresholdDays }}d)
                        </span>
                    @elseif ($this->slaThresholdDays !== null)
                        <span class="text-xs text-slate-500">•</span>
                        @if ($this->slaDaysRemaining <= 3)
                            <span
                                class="inline-flex items-center rounded-full bg-orange-500/10 px-2.5 py-0.5 text-xs font-semibold text-orange-400 ring-1 ring-orange-500/30"
                            >
                                SLA: {{ $this->slaDaysRemaining }}
                                {{ $this->slaDaysRemaining === 1 ? 'day' : 'days' }} remaining
                            </span>
                        @else
                            <span
                                class="inline-flex items-center rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-400 ring-1 ring-slate-700/50"
                            >
                                SLA: {{ $this->slaDaysRemaining }} days remaining
                            </span>
                        @endif
                    @endif
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if ($this->issueTrackerEnabled && !$this->findingStatus->jira_issue_key)
                <button
                    wire:click="createJiraTicket"
                    wire:loading.attr="disabled"
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition-colors hover:bg-cyan-500/20 disabled:opacity-50"
                >
                    <x-icon.spinner-arc
                        wire:loading
                        wire:target="createJiraTicket"
                        class="size-4 animate-spin"
                    />
                    <x-icon.plus
                        wire:loading.remove
                        wire:target="createJiraTicket"
                        class="size-4"
                    />
                    Create issue
                </button>
            @elseif ($this->issueTrackerBrowseUrl)
                <a
                    href="{{ $this->issueTrackerBrowseUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition-colors hover:bg-cyan-500/20"
                >
                    <x-icon.external-link class="size-4" />
                    {{ $this->findingStatus->jira_issue_key }}
                </a>
            @endif

            @can('findings.snooze')
                @if ($this->statusRaw === 'SNOOZED')
                    <button
                        wire:click="openUnsnoozeModal"
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-violet-500/30 bg-violet-500/10 px-4 py-2 text-sm font-medium text-violet-300 transition-colors hover:bg-violet-500/20"
                    >
                        <x-icon.snooze class="size-4" />
                        Unsnooze
                    </button>
                @else
                    <button
                        wire:click="openSnoozeModal"
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-violet-500/30 bg-violet-500/10 px-4 py-2 text-sm font-medium text-violet-300 transition-colors hover:bg-violet-500/20"
                    >
                        <x-icon.snooze class="size-4" />
                        Snooze finding
                    </button>
                @endif
            @endcan
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 border-b border-slate-800">
        <nav
            class="-mb-px flex space-x-8"
            aria-label="Tabs"
        >
            @foreach (['details' => 'Details', 'history' => 'History'] as $tab => $label)
                <button
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    @class([
                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors cursor-pointer',
                        'border-indigo-500 text-indigo-400' => $activeTab === $tab,
                        'border-transparent text-slate-400 hover:border-slate-700 hover:text-slate-300' =>
                            $activeTab !== $tab,
                    ])
                >
                    {{ $label }}
                    @if ($tab === 'history')
                        <span class="ml-1.5 rounded-full bg-slate-700/60 px-1.5 py-0.5 text-xs text-slate-400">
                            {{ count($this->history) }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    @if ($activeTab === 'details')
        @include('livewire.finding-detail._tab-details', [
            'statusRaw' => $this->statusRaw,
            'findingStatus' => $this->findingStatus,
            'findingDetails' => $this->findingDetails,
            'findingDetailRows' => $this->findingDetailRows,
            'packageInfoRows' => $this->packageInfoRows,
            'service' => $this->service,
            'githubUrl' => $this->githubUrl,
            'snippetViewData' => $this->snippetViewData,
        ])
    @endif

    @if ($activeTab === 'history')
        @include('livewire.finding-detail._tab-history', [
            'history' => $this->history,
        ])
    @endif

    @if ($showUnsnoozeModal)
        @include('livewire.finding-detail._modal-unsnooze')
    @endif

    @if ($showSnoozeModal)
        @include('livewire.finding-detail._modal-snooze', [
            'snoozeReason' => $snoozeReason,
        ])
    @endif

</div>
