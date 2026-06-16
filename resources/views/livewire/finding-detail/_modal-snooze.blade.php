@props(['snoozeReason'])

<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
    x-data
    x-on:keydown.escape.window="$wire.closeSnoozeModal()"
>
    <div
        class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
        @click.outside="$wire.closeSnoozeModal()"
    >

        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-base font-semibold text-slate-100">Snooze Finding</h3>
            @can('findings.snooze')
                <button
                    wire:click="closeSnoozeModal"
                    type="button"
                    class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200"
                >
                    <x-icon.x class="size-4" />
                </button>
            @endcan
        </div>
        <div class="space-y-4 px-6 py-4">
            <p class="text-sm text-slate-400">The finding will be hidden until you manually unsnooze it.</p>

            {{-- Preset chips --}}
            <div class="flex flex-wrap gap-2">
                @foreach (['Not applicable to our environment', 'False positive', 'Risk accepted by management', 'Will be addressed in next sprint', 'Temporary workaround active', 'Duplicate of other finding'] as $preset)
                    <button
                        wire:click="$set('snoozeReason', '{{ $preset }}')"
                        type="button"
                        @class([
                            'rounded-full border px-3 py-1 text-xs font-medium transition-colors cursor-pointer',
                            'border-violet-500/50 bg-violet-500/20 text-violet-200' =>
                                $snoozeReason === $preset,
                            'border-slate-700 bg-slate-800 text-slate-400 hover:border-slate-600 hover:text-slate-200' =>
                                $snoozeReason !== $preset,
                        ])
                    >
                        {{ $preset }}
                    </button>
                @endforeach
            </div>

            <label class="block space-y-1.5">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Reason <span
                        class="text-red-400"
                    >*</span></span>
                <textarea
                    wire:model="snoozeReason"
                    rows="3"
                    placeholder="Why is this finding being snoozed?"
                    class="{{ $errors->has('snoozeReason') ? 'border-red-500/60 focus:border-red-500/60' : 'border-slate-700 focus:border-violet-500/60' }} w-full resize-none rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/30"
                ></textarea>
                @error('snoozeReason')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-800 px-6 py-4">
            <button
                wire:click="closeSnoozeModal"
                type="button"
                class="inline-flex cursor-pointer items-center rounded-lg border border-slate-700 bg-transparent px-4 py-2 text-sm font-medium text-slate-300 transition-colors hover:bg-slate-800"
            >
                Cancel
            </button>
            <button
                wire:click="confirmSnooze"
                type="button"
                wire:loading.attr="disabled"
                :disabled="!$wire.snoozeReason.trim()"
                class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <x-icon.spinner
                    wire:loading
                    wire:target="confirmSnooze"
                    class="size-4 animate-spin"
                />
                Confirm Snooze
            </button>
        </div>
    </div>
</div>
