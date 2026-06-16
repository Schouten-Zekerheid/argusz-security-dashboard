<?php

namespace App\View\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;
use Illuminate\View\View;

class Pagination extends Component
{
    /** @var array<int, int|string> */
    public array $paginationTriggers;

    public int $currentPage;

    public function __construct(
        public LengthAwarePaginator $paginator,
        public string $label = 'results',
    ) {
        $this->currentPage = $paginator->currentPage();
        $this->paginationTriggers = $this->buildPaginationTriggers();
    }

    #[\Override]
    public function shouldRender(): bool
    {
        return $this->paginator->lastPage() > 1;
    }

    public function render(): View
    {
        return view('components.pagination');
    }

    /** @return array<int, int|string> */
    private function buildPaginationTriggers(): array
    {
        $currentPage = $this->currentPage;
        $lastPage = $this->paginator->lastPage();

        $numbers = array_values(
            array_unique([
                // always include first and last page
                1,
                $lastPage,

                // near start: keep first few pages visible
                $this->clamp(min(2, $lastPage), $currentPage, $lastPage),
                $this->clamp(min(3, $lastPage), $currentPage, $lastPage),
                $this->clamp(min(4, $lastPage), $currentPage, $lastPage),

                // around current page
                $this->clamp(1, $currentPage - 1, $lastPage),
                $this->clamp(1, $currentPage, $lastPage),
                $this->clamp(1, $currentPage + 1, $lastPage),

                // near end: keep last few pages visible
                $this->clamp(1, $currentPage, max(1, $lastPage - 3)),
                $this->clamp(1, $currentPage, max(1, $lastPage - 2)),
                $this->clamp(1, $currentPage, max(1, $lastPage - 1)),
            ]),
        );

        sort($numbers);

        $gapIndices = [];
        foreach ($numbers as $i => $page) {
            if ($i > 0 && abs($page - $numbers[$i - 1]) > 1) {
                $gapIndices[] = $i;
            }
        }

        $paginationTriggers = $numbers;
        $offset = 0;

        foreach ($gapIndices as $gapIndex) {
            $page = $numbers[$gapIndex];
            $prevPage = $numbers[$gapIndex - 1];
            $insertValue = abs($page - $prevPage) > 2 ? '...' : $page - 1;

            array_splice($paginationTriggers, $gapIndex + $offset, 0, [$insertValue]);
            $offset++;
        }

        return $paginationTriggers;
    }

    private function clamp(int $min, int $value, int $max): int
    {
        return max($min, min($value, $max));
    }
}
