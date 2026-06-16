<div class="border-t border-slate-800 bg-slate-950/40 px-6 py-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-slate-500">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of
            {{ $paginator->total() }} {{ $label }}
        </p>

        <nav
            class="flex items-center gap-1"
            aria-label="Pagination"
        >
            {{-- Previous --}}
            <button
                wire:click="previousPage"
                @disabled($paginator->onFirstPage())
                class="inline-flex size-8 cursor-pointer items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
            >
                &lsaquo;
            </button>

            {{-- Page triggers --}}
            @foreach ($paginationTriggers as $page)
                @if ($page === '...')
                    <span
                        class="inline-flex size-8 select-none items-center justify-center text-sm text-slate-500">...</span>
                @else
                    <button
                        wire:click="gotoPage({{ $page }})"
                        @class([
                            'inline-flex items-center justify-center size-8 rounded text-sm transition-colors cursor-pointer',
                            'bg-cyan-500/20 text-cyan-300 font-semibold' => $currentPage === $page,
                            'text-slate-400 hover:bg-slate-800 hover:text-slate-100' =>
                                $currentPage !== $page,
                        ])
                    >
                        {{ $page }}
                    </button>
                @endif
            @endforeach

            {{-- Next --}}
            <button
                wire:click="nextPage"
                @disabled($paginator->onLastPage())
                class="inline-flex size-8 cursor-pointer items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
            >
                &rsaquo;
            </button>
        </nav>
    </div>
</div>
