<div class="space-y-6">

    @include('livewire.pipeline-run-detail._header', [
        'serviceId' => $this->serviceId,
        'service' => $this->service,
        'runId' => $this->runId,
        'statCards' => $this->statCards,
        'runMeta' => $this->runMeta,
    ])

    @include('livewire.pipeline-run-detail._stat-cards', [
        'severityBreakdown' => $this->severityBreakdown,
        'statCards' => $this->statCards,
        'runMeta' => $this->runMeta,
    ])

    {{-- Main content: tool cards + sidebar --}}
    <div class="grid grid-cols-3 gap-6">

        {{-- Tool cards (2/3 width) --}}
        <div class="col-span-2 space-y-4">
            @forelse ($this->toolSections as $section)
                @include('livewire.pipeline-run-detail._tool-section', [
                    'section' => $section,
                ])
            @empty
                <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-8 text-center">
                    <p class="text-sm text-slate-500">No tool runs found for this pipeline run.</p>
                </div>
            @endforelse
        </div>

        {{-- Sidebar (1/3 width) --}}
        <div class="space-y-4">
            @include('livewire.pipeline-run-detail._sidebar', [
                'runMeta' => $this->runMeta,
                'fixedFindings' => $this->fixedFindings,
            ])
        </div>

    </div>

</div>
