<?php

namespace App\Livewire;

use App\Models\PipelineRun;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Service $service
 * @property-read Collection $allRuns
 * @property-read LengthAwarePaginator $paginatedRuns
 */
#[Layout('components.layouts.app')]
class ServiceRunHistory extends Component
{
    use WithPagination;

    public string $serviceId;

    public int $perPage = 25;

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'branch')]
    public string $filterBranch = '';

    #[Url]
    public ?string $type = null;

    public function mount(string $serviceId): void
    {
        $service = Service::find($serviceId);
        abort_if($service === null, 404);
        $this->serviceId = $serviceId;
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterBranch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function service(): Service
    {
        return Service::find($this->serviceId);
    }

    /**
     * All runs for this service, sorted descending by ingested_at in PHP
     * (CosmosDB has no index on ingested_at, so orderBy is not supported).
     */
    #[Computed]
    public function allRuns(): Collection
    {
        $query = PipelineRun::where('service_id', $this->serviceId);

        if ($this->type === 'azure') {
            $query->where('meta.tier', 'container');
        } else {
            $query->where('meta.tier', '!=', 'container');
        }

        return $query->get(['_id', 'meta', 'runs', 'ingested_at'])
            ->sortByDesc('ingested_at')
            ->map(function (PipelineRun $run): array {
                $meta = $run->meta ?? [];
                $repoUrl = $meta['repository_url']
                    ?? $this->service->repository_url
                    ?? null;
                $findingCount = collect($run->runs ?? [])
                    ->flatMap(fn ($r): array => $r['findings'] ?? [])
                    ->count();

                $toolStatuses = [];
                foreach ($run->runs ?? [] as $r) {
                    $category = data_get($r, 'tool.category', '');
                    if ($category !== '') {
                        $toolStatuses[$category] = data_get($r, 'scan.status', 'unknown');
                    }
                }

                return [
                    'run_id' => (string) $run->_id,
                    'ingested_at' => $run->ingested_at
                        ? Carbon::parse($run->ingested_at)->format('d M Y, H:i')
                        : '—',
                    'ingested_at_diff' => $run->ingested_at
                        ? Carbon::parse($run->ingested_at)->diffForHumans()
                        : null,
                    'actor' => $meta['actor'] ?? null,
                    'branch' => $meta['branch'] ?? null,
                    'commit_short' => isset($meta['commit_hash'])
                        ? substr((string) $meta['commit_hash'], 0, 7)
                        : null,
                    'commit_hash' => $meta['commit_hash'] ?? null,
                    'repository_url' => $repoUrl,
                    'finding_count' => $findingCount,
                    'tool_statuses' => $toolStatuses,
                    'status_text' => $findingCount > 0
                        ? $findingCount.' issues'
                        : 'Clean',
                    'status_class' => $findingCount > 0
                        ? 'text-red-400'
                        : 'text-green-400',
                ];
            })
            ->values();
    }

    #[Computed]
    public function paginatedRuns(): LengthAwarePaginator
    {
        $runs = $this->allRuns;

        if ($this->filterStatus === 'issues') {
            $runs = $runs->filter(fn ($r): bool => $r['finding_count'] > 0)->values();
        } elseif ($this->filterStatus === 'clean') {
            $runs = $runs->filter(fn ($r): bool => $r['finding_count'] === 0)->values();
        }

        if ($this->filterBranch !== '') {
            $branch = strtolower($this->filterBranch);
            $runs = $runs->filter(
                fn ($r): bool => str_contains(strtolower($r['branch'] ?? ''), $branch)
            )->values();
        }

        $page = $this->getPage();

        return new LengthAwarePaginator(
            $runs->forPage($page, $this->perPage)->values(),
            $runs->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function render(): View
    {
        return view('livewire.service-run-history')
            ->title('Scan History — '.$this->service->name);
    }
}
