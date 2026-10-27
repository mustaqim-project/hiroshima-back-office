<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * @req FR-AUTH-03, C2, FSD-01
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = config('rbac.permissions', []);
        $matrix = config('rbac.matrix', []);

        // Create all permissions
        foreach ($permissions as $code => $description) {
            Permission::firstOrCreate(['name' => $code, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $code, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions
        foreach ($matrix as $roleName => $assignedPermissions) {
            $webRole = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $webRole->syncPermissions($assignedPermissions);

            $sanctumRole = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            $sanctumRole->syncPermissions($assignedPermissions);
        }
    }
}
