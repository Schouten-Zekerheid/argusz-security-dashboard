<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogRepository
{
    /** @return Builder<Activity> */
    public function getActivityLogs(
        string $log = '',
        string $actor = '',
        string $from = '',
        string $to = '',
        string $event = '',
    ): Builder {
        return Activity::with('causer')
            ->when($log !== '', fn ($q) => $q->inLog($log))
            ->when($actor !== '', fn ($q) => $q->where('causer_id', $actor))
            ->when($from !== '', fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($event !== '', fn ($q) => $q->where('event', $event))
            ->latest();
    }

    /** @return Collection<int, User> */
    public function getUniqueActors(): Collection
    {
        /** @var Collection<int, User> $actors */
        $actors = Activity::whereNotNull('causer_id')
            ->where('causer_type', User::class)
            ->with('causer')
            ->distinct()
            ->get(['causer_id', 'causer_type'])
            ->map(fn (Activity $activity) => $activity->causer)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return $actors;
    }
}
