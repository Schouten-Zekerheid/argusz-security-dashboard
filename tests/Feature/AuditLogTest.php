<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure view.logs permission exists
        $viewLogsPermission = Permission::firstOrCreate([
            'name' => 'view.logs',
            'guard_name' => 'web',
        ]);

        // Create admin role and give permission
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($viewLogsPermission);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->unauthorizedUser = User::factory()->create();
    }

    public function test_unauthorized_user_cannot_access_audit_log(): void
    {
        $this->actingAs($this->unauthorizedUser);

        Livewire::test(AuditLog::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_access_audit_log(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AuditLog::class)
            ->assertOk();
    }
}
