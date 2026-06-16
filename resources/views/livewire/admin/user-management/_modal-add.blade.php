{{-- message: optional override; inside @error('name') Laravel also binds $message (the default error message) --}}
@props(['roles', 'nameMessage' => null, 'message' => null])

<x-modal close-action="closeModal">
    <div class="mb-5 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-100">Add User</h2>
        <button
            wire:click="closeModal"
            class="text-slate-500 transition-colors hover:text-slate-300"
        >
            <x-icon.x class="size-4" />
        </button>
    </div>

    <div class="space-y-4">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-400">Name</label>
            <input
                wire:model="name"
                type="text"
                placeholder="Full name"
                class="@error('name') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-600 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
            >
            @error('name')
                <p class="mt-1.5 text-xs text-red-400">{{ $nameMessage ?? $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-400">Email address</label>
            <input
                wire:model="email"
                type="email"
                placeholder="name@example.com"
                class="@error('email') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-600 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
            >
            @error('email')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        @if (config('integrations.auth.provider') === 'local')
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-400">Password</label>
                <input
                    wire:model="password"
                    type="password"
                    placeholder="At least 12 characters"
                    class="@error('password') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-100 placeholder-slate-600 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                >
                @error('password')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-400">Role</label>
            <select
                wire:model="selectedRole"
                class="@error('selectedRole') border-red-500/60 @else border-slate-700 @enderror w-full rounded-lg border bg-slate-800 px-3 py-2 text-sm text-slate-200 outline-none transition-colors focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
            >
                <option
                    value=""
                    class="text-slate-500"
                >— Choose a role —</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
            @error('selectedRole')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <button
            wire:click="closeModal"
            class="text-sm text-slate-500 transition-colors hover:text-slate-300"
        >
            Cancel
        </button>
        <button
            wire:click="saveUser"
            class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition-colors hover:bg-cyan-500/20"
        >
            Save
        </button>
    </div>
</x-modal>
