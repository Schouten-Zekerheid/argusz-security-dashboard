<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Clear Spatie's permission cache to prevent state issues
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Register the settings.manage permission
        $permission = Permission::firstOrCreate([
            'name' => 'settings.manage',
            'guard_name' => 'web',
        ]);

        // Assign permission to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        // Assign permission to rapporteur role
        $rapporteurRole = Role::where('name', 'rapporteur')->first();
        if ($rapporteurRole) {
            $rapporteurRole->givePermissionTo($permission);
        }

        // Assign permission to management role
        $managementRole = Role::where('name', 'management')->first();
        if ($managementRole) {
            $managementRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');

        // Clear cached permissions on rollback
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'settings.manage')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
