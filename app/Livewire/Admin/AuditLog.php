<?php

namespace App\Livewire\Admin;

use App\Models\FindingStatus;
use App\Repositories\ActivityLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * @property-read LengthAwarePaginator<int, Activity> $logs
 * @property-read Collection<string, FindingStatus> $findings
 * @property-read Collection<int, string> $uniqueActors
 * @property-read Collection<int, string> $eventNames
 */
#[Layout('components.layouts.app')]
#[Title('Audit Log')]
class AuditLog extends Component
{
    use WithPagination;

    #[Url(as: 'log')]
    public string $filterLog = '';

    #[Url(as: 'actor')]
    public string $filterActor = '';

    #[Url(as: 'from')]
    public string $filterFrom = '';

    #[Url(as: 'to')]
    public string $filterTo = '';

    #[Url(as: 'event')]
    public string $filterEvent = '';

    private ActivityLogRepository $activityLogRepository;

    public function boot(ActivityLogRepository $activityLogRepository): void
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    public function mount(): void
    {
        $this->authorize('view.logs');
    }

    public function updatingFilterLog(): void
    {
        $this->resetPage();
    }

    public function updatingFilterActor(): void
    {
        $this->resetPage();
    }

    public function updatingFilterFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTo(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterLog', 'filterActor', 'filterFrom', 'filterTo', 'filterEvent',
        ]);
        $this->resetPage();
    }

    #[Computed]
    public function logs()
    {
        $this->authorize('view.logs');

        return $this->activityLogRepository->getActivityLogs(
            log: $this->filterLog,
            actor: $this->filterActor,
            from: $this->filterFrom,
            to: $this->filterTo,
            event: $this->filterEvent,
        )->paginate(25);
    }

    #[Computed]
    public function findings(): Collection
    {
        $findingIds = collect($this->logs->items())
            ->map(fn ($log) => $log->properties->get('finding_id'))
            ->filter()
            ->unique()
            ->toArray();

        if (empty($findingIds)) {
            return collect();
        }

        return FindingStatus::whereIn('_id', $findingIds)->get()->keyBy(fn ($item): string => (string) $item->_id);
    }

    #[Computed]
    public function uniqueActors(): Collection
    {
        return $this->activityLogRepository->getUniqueActors();
    }

    #[Computed]
    public function logNames()
    {
        return Activity::distinct()->orderBy('log_name')->pluck('log_name');
    }

    #[Computed]
    public function eventNames()
    {
        return Activity::distinct()->orderBy('event')->pluck('event');
    }

    public function render(): View
    {
        return view('livewire.admin.audit-log');
    }
}
