{{-- Security score skeleton --}}
<div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="h-3 w-40 animate-pulse rounded bg-slate-800"></div>
            <div class="mt-3 h-12 w-28 animate-pulse rounded bg-slate-800"></div>
            <div class="mt-2 h-2.5 w-48 animate-pulse rounded bg-slate-800"></div>
        </div>
        <div class="size-24 animate-pulse rounded-full bg-slate-800"></div>
    </div>
</div>

{{-- KPI cards skeleton --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach (range(1, 4) as $_)
        <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
            <div class="h-3 w-28 animate-pulse rounded bg-slate-800"></div>
            <div class="mt-3 h-10 w-20 animate-pulse rounded bg-slate-800"></div>
            <div class="mt-2 h-2.5 w-16 animate-pulse rounded bg-slate-800"></div>
        </div>
    @endforeach
</div>

{{-- Top row charts skeleton --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5 xl:col-span-2">
        <div class="h-3 w-48 animate-pulse rounded bg-slate-800"></div>
        <x-chart-skeleton class="mt-4 h-56" />
    </div>
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="h-3 w-36 animate-pulse rounded bg-slate-800"></div>
        <x-chart-skeleton class="mt-4 h-56" />
    </div>
</div>

{{-- Critical by repo skeleton --}}
<div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
    <div class="h-3 w-64 animate-pulse rounded bg-slate-800"></div>
    <x-chart-skeleton class="mt-4 h-56" />
</div>

{{-- MTTR + new findings skeleton --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="h-3 w-52 animate-pulse rounded bg-slate-800"></div>
        <x-chart-skeleton class="mt-4 h-56" />
    </div>
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="h-3 w-44 animate-pulse rounded bg-slate-800"></div>
        <x-chart-skeleton class="mt-4 h-56" />
    </div>
</div>

{{-- Risk score + velocity skeleton --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="h-3 w-44 animate-pulse rounded bg-slate-800"></div>
        <div class="mt-4 space-y-4">
            @foreach (range(1, 5) as $_)
                <div>
                    <div class="mb-1.5 flex justify-between">
                        <div class="h-2.5 w-32 animate-pulse rounded bg-slate-800"></div>
                        <div class="h-2.5 w-8 animate-pulse rounded bg-slate-800"></div>
                    </div>
                    <div class="h-2 w-full animate-pulse rounded-full bg-slate-800"></div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
        <div class="h-3 w-52 animate-pulse rounded bg-slate-800"></div>
        <x-chart-skeleton class="mt-4 h-72" />
    </div>
</div>

{{-- SLA aging skeleton --}}
<div class="shadow-xs rounded-xl border border-slate-800 bg-slate-900 p-5">
    <div class="h-3 w-56 animate-pulse rounded bg-slate-800"></div>
    <x-chart-skeleton class="mt-4 h-56" />
</div>

{{-- Repo comparison skeleton --}}
<div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="border-b border-slate-800 bg-slate-950/40 px-6 py-4">
        <div class="h-4 w-48 animate-pulse rounded bg-slate-800"></div>
        <div class="mt-1.5 h-3 w-72 animate-pulse rounded bg-slate-800"></div>
    </div>
    <div class="px-6 py-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach (range(1, 3) as $_)
                <div class="h-9 animate-pulse rounded-lg bg-slate-800"></div>
            @endforeach
        </div>
    </div>
</div>
