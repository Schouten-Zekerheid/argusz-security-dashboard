<div class="space-y-6">

    <x-flash-messages />

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">SLA Settings</h1>
            <p class="mt-0.5 text-sm text-slate-500">Configure the SLA target periods (in days) per severity level</p>
        </div>
    </div>

    {{-- Settings Card --}}
    <div class="max-w-2xl overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-xl">
        <div class="border-b border-slate-800 bg-slate-950/40 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-200">SLA periods (in days)</h2>
        </div>

        <form
            wire:submit="saveSettings"
            class="space-y-6 p-6"
        >
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Critical SLA --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-red-400">Critical
                        SLA</label>
                    <div class="relative rounded-md shadow-sm">
                        <input
                            wire:model="critical"
                            type="number"
                            min="1"
                            class="@error('critical') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                            placeholder="7"
                        >
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-sm text-slate-500">days</span>
                        </div>
                    </div>
                    @error('critical')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- High SLA --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-orange-400">High
                        SLA</label>
                    <div class="relative rounded-md shadow-sm">
                        <input
                            wire:model="high"
                            type="number"
                            min="1"
                            class="@error('high') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                            placeholder="30"
                        >
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-sm text-slate-500">days</span>
                        </div>
                    </div>
                    @error('high')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Medium SLA --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-amber-400">Medium
                        SLA</label>
                    <div class="relative rounded-md shadow-sm">
                        <input
                            wire:model="medium"
                            type="number"
                            min="1"
                            class="@error('medium') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                            placeholder="90"
                        >
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-sm text-slate-500">days</span>
                        </div>
                    </div>
                    @error('medium')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Low SLA --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-lime-400">Low
                        SLA</label>
                    <div class="relative rounded-md shadow-sm">
                        <input
                            wire:model="low"
                            type="number"
                            min="1"
                            class="@error('low') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                            placeholder="180"
                        >
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-sm text-slate-500">days</span>
                        </div>
                    </div>
                    @error('low')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Validation Info Alert --}}
            <div class="rounded-lg border border-slate-800/80 bg-slate-950/40 p-4">
                <div class="flex">
                    <div class="mt-0.5 shrink-0">
                        <svg
                            class="h-4 w-4 text-indigo-400"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-xs leading-relaxed text-slate-400">
                            The periods must be configured in a logical and strictly ascending order. The following
                            applies: <code class="font-mono text-indigo-300">Critical (red) &le; High (orange) &le;
                                Medium (yellow) &le; Low (green)</code>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Save Action --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-800 pt-4">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition-colors hover:bg-cyan-500/20 disabled:opacity-50"
                >
                    <svg
                        wire:loading
                        wire:target="saveSettings"
                        class="size-4 animate-spin text-cyan-300"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <svg
                        wire:loading.remove
                        wire:target="saveSettings"
                        class="size-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Save settings
                </button>
            </div>
        </form>
    </div>

</div>
