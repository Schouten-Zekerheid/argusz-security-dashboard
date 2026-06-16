<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
    x-data
    x-on:keydown.escape.window="$wire.closeUnsnoozeModal()"
>
    <div
        class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
        @click.outside="$wire.closeUnsnoozeModal()"
    >
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-100">Unsnooze Finding</h3>
            <button
                wire:click="closeUnsnoozeModal"
                type="button"
                class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200"
            >
                <x-icon.x class="size-4" />
            </button>
        </div>
        <div class="px-6 py-4">
            <p class="text-sm text-slate-400">The finding will be marked as
                <span class="font-medium text-slate-200">open</span> again.
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-800 px-6 py-4">
            <button
                wire:click="closeUnsnoozeModal"
                type="button"
                class="inline-flex cursor-pointer items-center rounded-lg border border-slate-700 bg-transparent px-4 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-slate-800"
            >
                Cancel
            </button>
            <button
                wire:click="unsnooze"
                type="button"
                wire:loading.attr="disabled"
                class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-violet-500 disabled:opacity-60"
            >
                <x-icon.spinner
                    wire:loading
                    wire:target="unsnooze"
                    class="size-4 animate-spin"
                />
                Unsnooze
            </button>
        </div>
    </div>
</div>
