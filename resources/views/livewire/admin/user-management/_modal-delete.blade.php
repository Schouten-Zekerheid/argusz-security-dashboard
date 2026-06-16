{{-- message: optional override; inside @error('name') Laravel also binds $message (the default error message) --}}
@props(['roles', 'nameMessage' => null, 'message' => null])

<div class="fixed inset-0 z-50 flex items-center justify-center">
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        wire:click="closeDeleteConfirmModal"
    ></div>
    <div
        x-data="{ typed: '' }"
        class="relative z-10 w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
    >
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-100">Delete User</h2>
            <button
                wire:click="closeDeleteConfirmModal"
                class="cursor-pointer text-slate-500 transition-colors hover:text-slate-300"
            >
                <x-icon.x class="size-4" />
            </button>
        </div>

        <p class="mb-4 text-sm text-slate-400">
            You are about to delete <span class="font-medium text-slate-200">{{ $detailUser->name }}</span>.
            This cannot be undone.
        </p>

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-400">
                Type the email address to confirm
                <span class="ml-1 font-mono text-slate-300">{{ $detailUser->email }}</span>
            </label>
            <input
                x-model="typed"
                wire:model="deleteConfirmEmail"
                type="email"
                placeholder="{{ $detailUser->email }}"
                class="@error('deleteConfirmEmail') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-600 outline-none transition-colors focus:border-red-500/60 focus:ring-1 focus:ring-red-500/30"
            >
            @error('deleteConfirmEmail')
                <p class="mt-1.5 text-xs text-red-400">{{ $deleteConfirmEmailMessage ?? $message }}</p>
            @enderror
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button
                wire:click="closeDeleteConfirmModal"
                class="cursor-pointer text-sm text-slate-500 transition-colors hover:text-slate-300"
            >
                Cancel
            </button>
            <button
                wire:click="deleteUser"
                :disabled="typed !== '{{ $detailUser->email }}'"
                class="cursor-pointer rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-400 transition-colors hover:bg-red-500/20 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-red-500/10"
            >
                Permanently delete
            </button>
        </div>
    </div>
</div>
