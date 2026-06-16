<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * All permissions the application uses.
     *
     * @var list<string>
     */
    private const PERMISSIONS = [
        'dashboard.view-management',
        'users.manage',
        'settings.manage',
        'services.manage',
        'reports.export',
        'findings.snooze',
        'view.logs',
    ];

    /**
     * Roles and the permissions granted to each.
     *
     * - `management` has full access.
     * - `developer` can view the management dashboard only.
     *
     * @var array<string, list<string>>
     */
    private const ROLES = [
        'management' => self::PERMISSIONS,
        'developer' => ['dashboard.view-management'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
