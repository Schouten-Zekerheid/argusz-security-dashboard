<?php

namespace App\Livewire;

use App\Enums\Severity;
use App\Models\FindingStatus;
use App\Models\Service;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Collection<int, array{
 *   id: string, service_name: string, service_url: string|null,
 *   title: string, tool: string, severity: string, type: string,
 *   status: string, reference_id: string|null, status_updated_at: mixed,
 * }> $findings
 */
#[Layout('components.layouts.app')]
#[Title('Findings')]
class Findings extends Component
{
    use WithPagination;

    public int $perPage = 25;

    #[Url(as: 'service')]
    public string $serviceFilter = '';

    #[Url(as: 'tool')]
    public string $toolFilter = '';

    #[Url(as: 'severity')]
    public string $severityFilter = '';

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'source')]
    public string $scanSourceFilter = '';

    /** @var array<int, array{key: string, dir: string}> */
    public array $sortColumns = [];

    public function updatingServiceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingToolFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingScanSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->serviceFilter = '';
        $this->toolFilter = '';
        $this->severityFilter = '';
        $this->typeFilter = '';
        $this->statusFilter = '';
        $this->scanSourceFilter = '';
        $this->resetPage();
    }

    public function toggleFilterValue(string $filter, string $value): void
    {
        if (! in_array($filter, ['serviceFilter', 'toolFilter', 'severityFilter', 'statusFilter'], true)) {
            return;
        }

        $value = trim($value);

        if ($value === '') {
            return;
        }

        $values = $this->csvFilterValues($this->{$filter});

        $this->{$filter} = in_array($value, $values, true)
            ? implode(',', array_values(array_filter($values, fn (string $selected): bool => $selected !== $value)))
            : implode(',', [...$values, $value]);

        $this->resetPage();
    }

    public function clearFilter(string $filter): void
    {
        if (! in_array($filter, ['serviceFilter', 'toolFilter', 'severityFilter', 'statusFilter'], true)) {
            return;
        }

        $this->{$filter} = '';
        $this->resetPage();
    }

    /** @return array<int, non-falsy-string> */
    public function selectedFilterValues(string $filter): array
    {
        if (! in_array($filter, ['serviceFilter', 'toolFilter', 'severityFilter', 'statusFilter'], true)) {
            return [];
        }

        return $this->csvFilterValues($this->{$filter});
    }

    /** @return array<string, array{dir: string, position: int}> */
    #[Computed]
    public function sortColumnMap(): array
    {
        $result = [];
        foreach ($this->sortColumns as $index => $sort) {
            $result[$sort['key']] = ['dir' => $sort['dir'], 'position' => $index + 1];
        }

        return $result;
    }

    public function sort(string $column): void
    {
        $allowed = [
            'service_name', 'title', 'severity', 'type',
            'status', 'reference_id', 'status_updated_at',
        ];
        if (! in_array($column, $allowed, true)) {
            return;
        }

        $existing = collect($this->sortColumns)->firstWhere('key', $column);

        if ($existing === null) {
            $this->sortColumns[] = ['key' => $column, 'dir' => 'asc'];
        } elseif ($existing['dir'] === 'asc') {
            $this->sortColumns = collect($this->sortColumns)
                ->map(fn ($s): array => $s['key'] === $column
                    ? ['key' => $column, 'dir' => 'desc']
                    : $s)
                ->values()
                ->all();
        } else {
            $this->sortColumns = collect($this->sortColumns)
                ->filter(fn ($s): bool => $s['key'] !== $column)
                ->values()
                ->all();
        }

        $this->resetPage();
    }

    #[Computed]
    public function findings(): Collection
    {
        $query = FindingStatus::query();

        $serviceIds = $this->csvFilterValues($this->serviceFilter);
        if (count($serviceIds) === 1) {
            $query->where('service_id', $serviceIds[0]);
        } elseif ($serviceIds !== []) {
            $query->whereIn('service_id', $serviceIds);
        }

        $tools = $this->csvFilterValues($this->toolFilter);
        if (count($tools) === 1) {
            $query->where('tool.key', $tools[0]);
        } elseif ($tools !== []) {
            $query->whereIn('tool.key', $tools);
        }

        $severities = collect($this->csvFilterValues($this->severityFilter))
            ->map(fn (string $severity): string => strtoupper($severity))
            ->values()
            ->all();
        if (count($severities) === 1) {
            $query->where('severity', $severities[0]);
        } elseif ($severities !== []) {
            $query->whereIn('severity', $severities);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter !== '') {
            $statuses = $this->csvFilterValues($this->statusFilter, lowercase: true);

            if (count($statuses) === 1) {
                $query->where('current_status', $statuses[0]);
            } elseif ($statuses !== []) {
                $query->whereIn('current_status', $statuses);
            }
        } else {
            $query->where('current_status', '!=', 'snoozed');
        }

        if ($this->scanSourceFilter !== '') {
            if ($this->scanSourceFilter === 'azure') {
                $query->where('scan_source', 'container');
            } elseif ($this->scanSourceFilter === 'github') {
                $query->where(function ($query): void {
                    $query->where('scan_source', 'github')
                        ->orWhereNull('scan_source');
                });
            }
        }

        $rawFindings = $query
            ->get([
                '_id', 'service_id', 'title', 'tool', 'severity', 'type',
                'status', 'current_status', 'reference_id', 'status_updated_at',
            ])
            ->values();

        if ($rawFindings->isEmpty()) {
            return collect();
        }

        $serviceIds = $rawFindings
            ->pluck('service_id')
            ->filter()
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        $servicesById = Service::whereIn('_id', $serviceIds)
            ->get(['_id', 'name', 'repository_url'])
            ->keyBy(fn ($service): string => (string) $service->_id);

        $findings = $rawFindings->map(
            function (FindingStatus $finding) use ($servicesById): array {
                $serviceId = (string) $finding->service_id;
                $service = $servicesById->get($serviceId);

                $severity = Severity::fromValue($finding->severity);

                return [
                    'id' => (string) $finding->_id,
                    'service_name' => $service !== null
                        ? $service->name
                        : 'Unknown service',
                    'service_url' => $service !== null ? $service->repository_url : null,
                    'title' => $this->normalizeString(
                        $finding->title,
                        'Untitled finding'
                    ),
                    'tool' => $this->normalizeString($finding->tool, 'unknown'),
                    'severity' => $severity->value,
                    'severity_badge' => $severity->badgeClass(),
                    'type' => $this->normalizeString($finding->type, 'unknown'),
                    'status' => $this->normalizeString(
                        $finding->status ?? $finding->current_status,
                        'unknown',
                    ),
                    'status_badge' => match (strtoupper($this->normalizeString(
                        $finding->status ?? $finding->current_status,
                        'unknown',
                    ))) {
                        'OPEN' => 'bg-red-500/20 text-red-200 ring-1 ring-red-400/60',
                        'SNOOZED' => 'bg-violet-500/20 text-violet-200 ring-1 ring-violet-400/60',
                        'RESOLVED',
                        'CLOSED' => 'bg-healthy/20 text-healthy ring-1 ring-healthy/60',
                        default => 'bg-slate-700/40 text-slate-200 ring-1 ring-slate-500/60',
                    },
                    'reference_id' => $this->normalizeNullableString(
                        $finding->reference_id
                    ),
                    'status_updated_at' => $finding->status_updated_at,
                ];
            }
        );

        if ($this->sortColumns === []) {
            $findings = $findings->sortByDesc('status_updated_at');
        } else {
            // Apply sorts in reverse order — PHP's stable sort preserves prior
            // ordering, so the first column clicked ends up as the primary sort key.
            $getOrder = fn ($f): int => Severity::fromValue($f['severity'] ?? null)->sortWeight();
            foreach (array_reverse($this->sortColumns) as $sort) {
                if ($sort['key'] === 'severity') {
                    $findings = $sort['dir'] === 'asc'
                        ? $findings->sortBy($getOrder)
                        : $findings->sortByDesc($getOrder);
                } elseif ($sort['dir'] === 'asc') {
                    $findings = $findings->sortBy($sort['key']);
                } else {
                    $findings = $findings->sortByDesc($sort['key']);
                }
            }
        }

        return $findings->values();
    }

    /** @return Collection<int, array{id: string, name: string}> */
    #[Computed]
    public function serviceOptions(): Collection
    {
        return Service::query()
            ->get(['_id', 'name'])
            ->map(fn (Service $service): array => [
                'id' => (string) $service->_id,
                'name' => (string) $service->name,
            ])
            ->sortBy('name')
            ->values();
    }

    /** @return Collection<int, non-falsy-string> */
    #[Computed]
    public function toolOptions(): Collection
    {
        return FindingStatus::query()
            ->pluck('tool.key')
            ->map(function (mixed $tool): ?string {
                if (is_array($tool)) {
                    $tool = collect($tool)
                        ->filter(fn ($item): bool => is_string($item) && $item !== '')
                        ->first();
                }

                if (! is_string($tool) || $tool === '') {
                    return null;
                }

                return $tool;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function severityOptions(): Collection
    {
        return collect([
            Severity::Critical,
            Severity::High,
            Severity::Medium,
            Severity::Low,
            Severity::Unknown,
        ])->map(fn (Severity $severity): string => strtolower($severity->value));
    }

    /** @return Collection<int, non-falsy-string> */
    #[Computed]
    public function typeOptions(): Collection
    {
        return FindingStatus::query()
            ->pluck('type')
            ->map(function (mixed $type): ?string {
                if (is_array($type)) {
                    $type = collect($type)
                        ->filter(fn ($item): bool => is_string($item) && $item !== '')
                        ->first();
                }

                if (! is_string($type) || $type === '') {
                    return null;
                }

                return $type;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /** @return array<int, non-falsy-string> */
    private function csvFilterValues(string $value, bool $lowercase = false): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->map(fn (string $item): string => $lowercase ? strtolower($item) : $item)
            ->unique()
            ->values()
            ->all();
    }

    /** @return Collection<int, lowercase-string&non-falsy-string> */
    #[Computed]
    public function statusOptions(): Collection
    {
        $statuses = FindingStatus::query()
            ->get(['status', 'current_status'])
            ->flatMap(fn (FindingStatus $finding): array => [
                $finding->status ?? null,
                $finding->current_status ?? null,
            ]);

        return $statuses
            ->map(function (mixed $status): ?string {
                if (is_array($status)) {
                    $status = collect($status)
                        ->filter(fn ($item): bool => is_string($item) && $item !== '')
                        ->first();
                }

                if (! is_string($status) || $status === '') {
                    return null;
                }

                return strtolower($status);
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    #[Computed]
    public function paginatedFindings(): LengthAwarePaginator
    {
        $allFindings = $this->findings;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $allFindings->forPage($page, $this->perPage)->values(),
            $allFindings->count(),
            $this->perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    public function render(): View
    {
        return view('livewire.findings');
    }

    private function normalizeString(mixed $value, string $fallback): string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item): bool => ! is_null($item) && $item !== '')
                ->first();
        }

        if (is_null($value) || $value === '') {
            return $fallback;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $fallback;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item): bool => ! is_null($item) && $item !== '')
                ->first();
        }

        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
