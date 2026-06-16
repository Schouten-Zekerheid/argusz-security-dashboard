<div class="space-y-6">

    <x-flash-messages />

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-100">User Management</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $this->users->count() }}
                user{{ $this->users->count() !== 1 ? 's' : '' }}</p>
        </div>
        <button
            wire:click="openModal"
            class="inline-flex items-center gap-2 rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-300 transition-colors hover:bg-cyan-500/20"
        >
            <svg
                class="size-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 5v14m-7-7h14"
                />
            </svg>
            Add User
        </button>
    </div>

    {{-- Users table --}}
    <div class="shadow-xs overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        <table class="min-w-full divide-y divide-slate-800 text-sm">
            <thead class="bg-slate-950/60">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-400">Role
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($this->users as $user)
                    <tr
                        wire:click="openDetailModal({{ $user->id }})"
                        class="cursor-pointer transition-colors hover:bg-slate-800/40"
                    >
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-100">{{ $user->name }}</span>
                                @if ($user->id === $currentUserId)
                                    <span
                                        class="inline-flex items-center rounded-full bg-cyan-500/10 px-2 py-0.5 text-xs font-medium text-cyan-400 ring-1 ring-cyan-400/30"
                                    >you</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-400">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-300 ring-1 ring-slate-700"
                            >
                                {{ $user->getRoleNames()->first() ?? '—' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showDetailModal && $this->detailUser)
        @include('livewire.admin.user-management._modal-detail', [
            'detailUser' => $this->detailUser,
            'currentUserId' => $currentUserId,
            'roles' => $roles,
        ])
    @endif

    @if ($showDeleteModal && $this->detailUser)
        @include('livewire.admin.user-management._modal-delete', [
            'detailUser' => $this->detailUser,
            'deleteConfirmEmailMessage' => $errors->first('deleteConfirmEmail'),
        ])
    @endif

    @if ($showModal)
        @include('livewire.admin.user-management._modal-add', [
            'roles' => $roles,
            'nameMessage' => $errors->first('name'),
        ])
    @endif

</div>
