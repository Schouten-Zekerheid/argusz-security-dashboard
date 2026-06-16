<?php

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

        // Get the settings.manage permission
        $permission = Permission::where('name', 'settings.manage')->first();

        if ($permission) {
            $rapporteurRole = Role::where('name', 'rapporteur')->first();
            if ($rapporteurRole) {
                $rapporteurRole->givePermissionTo($permission);
            }

            $managementRole = Role::where('name', 'management')->first();
            if ($managementRole) {
                $managementRole->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'settings.manage')->first();

        if ($permission) {
            $rapporteurRole = Role::where('name', 'rapporteur')->first();
            if ($rapporteurRole) {
                $rapporteurRole->revokePermissionTo($permission);
            }

            $managementRole = Role::where('name', 'management')->first();
            if ($managementRole) {
                $managementRole->revokePermissionTo($permission);
            }
        }
    }
};
