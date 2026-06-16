<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear Spatie's permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Ensure Management role exists
        $managementRole = Role::firstOrCreate(['name' => 'management', 'guard_name' => 'web']);

        // 2. Give Management all existing permissions
        $allPermissions = Permission::all();
        $managementRole->syncPermissions($allPermissions);

        // 3. Find old roles
        $oldRoles = Role::whereIn('name', ['admin', 'rapporteur'])->get();

        foreach ($oldRoles as $oldRole) {
            // 4. Move all users from the old role to Management
            $users = User::role($oldRole->name)->get();
            foreach ($users as $user) {
                $user->assignRole($managementRole);
                $user->removeRole($oldRole);
            }

            // 5. Delete the old role
            $oldRole->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        // No revert needed as management role is a permanent part of the new structure
    }
};
