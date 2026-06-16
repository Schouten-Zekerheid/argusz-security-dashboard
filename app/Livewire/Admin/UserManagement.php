<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * @property-read Collection<int, User> $users
 * @property-read User|null $detailUser
 */
#[Layout('components.layouts.app')]
#[Title('User Management')]
class UserManagement extends Component
{
    // --- Add-user modal state ---
    public bool $showModal = false;

    #[Rule('required|string|max:255')]
    public string $name = '';

    public string $email = '';

    public string $password = '';

    #[Rule('required|exists:roles,name')]
    public string $selectedRole = '';

    // --- Detail modal state ---
    public bool $showDetailModal = false;

    public ?int $selectedUserId = null;

    public string $detailRole = '';

    // --- Delete confirmation modal state ---
    public bool $showDeleteModal = false;

    public string $deleteConfirmEmail = '';

    // --- Shared ---
    public array $roles = [];

    public int $currentUserId;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $emailRules = ['required', 'email', 'max:255', 'unique:users,email'];

        $allowedDomains = config('security.users.allowed_email_domains', []);
        if ($allowedDomains !== []) {
            $emailRules[] = 'ends_with:'.implode(',', array_map(
                fn (string $domain): string => "@{$domain}",
                $allowedDomains
            ));
        }

        return [
            'email' => $emailRules,
            'password' => $this->localAuth()
                ? ['required', 'string', 'min:12']
                : ['nullable'],
        ];
    }

    private function localAuth(): bool
    {
        return config('integrations.auth.provider') === 'local';
    }

    public function mount(): void
    {
        $this->currentUserId = auth()->id();
        $this->roles = Role::orderBy('name')->pluck('name')->toArray();
    }

    public function openModal(): void
    {
        $this->authorize('users.manage');
        $this->reset(['name', 'email', 'password', 'selectedRole']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function saveUser(): void
    {
        $this->authorize('users.manage');

        try {
            $this->validate();
        } catch (ValidationException $e) {
            activity()
                ->useLog('users')
                ->causedBy(auth()->user())
                ->event('user_validation_failed')
                ->withProperties([
                    'name' => $this->name,
                    'email' => $this->email,
                    'errors' => implode(', ', array_merge(...array_values($e->errors()))),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('User creation failed (validation error)');
            throw $e;
        }

        try {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->localAuth() ? Hash::make($this->password) : null,
            ]);

            $user->syncRoles([$this->selectedRole]);
        } catch (Throwable $e) {
            Log::error('Gebruiker aanmaken mislukt', [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->selectedRole,
                'error' => $e->getMessage(),
            ]);
            activity()
                ->useLog('users')
                ->causedBy(auth()->user())
                ->event('user_create_failed')
                ->withProperties([
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->selectedRole,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('User creation failed (database/system error)');

            session()->flash(
                'flash.error',
                'An error occurred while creating the user.',
            );

            return;
        }

        activity()
            ->useLog('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('user_created')
            ->withProperties([
                'name' => $user->name,
                'email' => $user->email,
                'role' => $this->selectedRole,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("User {$user->name} created");

        $this->closeModal();
        session()->flash('flash.success', "User {$user->name} created.");
    }

    public function openDetailModal(int $userId): void
    {
        $this->authorize('users.manage');
        $this->selectedUserId = $userId;
        $this->detailRole = $this->users
            ->firstWhere('id', $userId)
            ?->getRoleNames()
            ->first() ?? '';
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedUserId = null;
        $this->detailRole = '';
    }

    public function openDeleteConfirmModal(): void
    {
        $this->authorize('users.manage');
        $this->deleteConfirmEmail = '';
        $this->showDetailModal = false;
        $this->showDeleteModal = true;
    }

    public function closeDeleteConfirmModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteConfirmEmail = '';
        $this->selectedUserId = null;
    }

    public function saveDetailRole(): void
    {
        $this->authorize('users.manage');

        if ($this->selectedUserId === auth()->id()) {
            throw ValidationException::withMessages([
                'detailRole' => 'Je kunt je eigen rol niet aanpassen.',
            ]);
        }

        $this->validateOnly('detailRole', [
            'detailRole' => 'required|exists:roles,name',
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $oldRole = $user->getRoleNames()->first() ?? '';
        $user->syncRoles([$this->detailRole]);

        activity()
            ->useLog('users')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('role_changed')
            ->withProperties([
                'old_role' => $oldRole,
                'new_role' => $this->detailRole,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Role of {$user->name} changed from '{$oldRole}' to"
                ." '{$this->detailRole}'");

        session()->flash('flash.success', "Role of {$user->name} updated.");
        $this->closeDetailModal();
    }

    public function deleteUser(): void
    {
        $this->authorize('users.manage');

        $user = User::findOrFail($this->selectedUserId);

        if ($user->email !== $this->deleteConfirmEmail) {
            $this->addError('deleteConfirmEmail', 'Email address does not match.');

            return;
        }

        $name = $user->name;
        $email = $user->email;
        $role = $user->getRoleNames()->first() ?? '';
        $user->delete();
        $this->closeDeleteConfirmModal();

        activity()
            ->useLog('users')
            ->causedBy(auth()->user())
            ->event('user_deleted')
            ->withProperties([
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("User {$name} deleted");

        session()->flash('flash.success', "User {$name} deleted.");
    }

    #[Computed]
    public function users()
    {
        $this->authorize('users.manage');

        return User::with('roles')->orderBy('name')->get();
    }

    #[Computed]
    public function detailUser(): ?User
    {
        if ($this->selectedUserId === null) {
            return null;
        }

        return $this->users->firstWhere('id', $this->selectedUserId);
    }

    public function render(): View
    {
        return view('livewire.admin.user-management');
    }
}
