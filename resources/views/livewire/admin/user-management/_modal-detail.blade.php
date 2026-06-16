@props(['detailUser', 'currentUserId', 'roles'])

<x-modal close-action="closeDetailModal">
    <div class="mb-5 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h2 class="text-base font-semibold text-slate-100">{{ $detailUser->name }}</h2>
            @if ($detailUser->id === $currentUserId)
                <span
                    class="inline-flex items-center rounded-full bg-cyan-500/10 px-2 py-0.5 text-xs font-medium text-cyan-400 ring-1 ring-cyan-400/30"
                >
                    you
                </span>
            @endif
        </div>
        <button
            wire:click="closeDetailModal"
            class="text-slate-500 transition-colors hover:text-slate-300"
        >
            <x-icon.x class="size-4" />
        </button>
    </div>

    <div class="space-y-4">
        <div>
            <p class="mb-1 text-xs font-medium text-slate-400">Email address</p>
            <p class="text-sm text-slate-300">{{ $detailUser->email }}</p>
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-400">Role</label>
            <select
                wire:model="detailRole"
                @disabled($detailUser->id === $currentUserId)
                class="@error('detailRole') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-200 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                @foreach ($roles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
            @error('detailRole')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center justify-between">
        @if ($detailUser->id !== $currentUserId)
            <button
                wire:click="openDeleteConfirmModal"
                class="cursor-pointer rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-400 transition-colors hover:bg-red-500/20"
            >
                Delete User
            </button>
        @else
            <span></span>
        @endif

        <div class="flex items-center gap-3">
            <button
                wire:click="closeDetailModal"
                class="text-sm text-slate-500 transition-colors hover:text-slate-300"
            >
                Cancel
            </button>
            @if ($detailUser->id !== $currentUserId)
                <button
                    wire:click="saveDetailRole"
                    class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition-colors hover:bg-cyan-500/20"
                >
                    Save role
                </button>
            @endif
        </div>
    </div>
</x-modal>
