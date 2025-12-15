<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use SavyApps\LaravelStudio\Enums\Permission as PermissionEnum;
use SavyApps\LaravelStudio\Models\Permission;
use SavyApps\LaravelStudio\Models\Role;
use SavyApps\LaravelStudio\Services\AuthorizationService;

/**
 * Seeder for permissions and role assignments.
 *
 * This seeder:
 * 1. Syncs permissions from the Permission enum
 * 2. Assigns appropriate permissions to each role
 * 3. Creates a super admin user
 */
class PermissionSeeder extends Seeder
{
    /**
     * Seed permissions and assign them to roles.
     */
    public function run(): void
    {
        // First, sync permissions from enum and resources
        $this->syncPermissions();

        // Then assign permissions to roles
        $this->assignPermissionsToRoles();

        // Create super admin user if it doesn't exist
        $this->createSuperAdminUser();
    }

    /**
     * Sync permissions from Permission enum and registered resources.
     */
    protected function syncPermissions(): void
    {
        try {
            $authService = app(AuthorizationService::class);
            $synced = $authService->syncPermissions();

            $this->command->info('Synced ' . count($synced) . ' permissions.');
        } catch (\Exception $e) {
            $this->command->warn('Could not sync permissions from service: ' . $e->getMessage());

            // Fall back to creating base permissions from enum
            $this->createBasePermissions();
        }
    }

    /**
     * Create base permissions from the Permission enum.
     */
    protected function createBasePermissions(): void
    {
        $count = 0;

        foreach (PermissionEnum::grouped() as $group => $permissions) {
            foreach ($permissions as $name => $displayName) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'group' => $group,
                        'description' => null,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Created {$count} base permissions from Permission enum.");
    }

    /**
     * Assign permissions to roles based on the Permission enum.
     */
    protected function assignPermissionsToRoles(): void
    {
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            $this->command->warn('No permissions found to assign.');

            return;
        }

        // Get roles
        $superAdminRole = Role::where('slug', Role::SUPER_ADMIN)->first();
        $adminRole = Role::where('slug', Role::ADMIN)->first();
        $userRole = Role::where('slug', Role::USER)->first();

        // Super admin gets all permissions (though they bypass checks anyway)
        if ($superAdminRole) {
            $superAdminPermissions = PermissionEnum::forSuperAdmin();
            $permissionIds = $allPermissions
                ->whereIn('name', $superAdminPermissions)
                ->pluck('id')
                ->toArray();

            $superAdminRole->permissions()->sync($permissionIds);
            $this->command->info('Assigned ' . count($permissionIds) . ' permissions to Super Admin role.');
        }

        // Admin gets all permissions except permission management
        if ($adminRole) {
            $adminPermissions = PermissionEnum::forAdmin();
            $permissionIds = $allPermissions
                ->whereIn('name', $adminPermissions)
                ->pluck('id')
                ->toArray();

            $adminRole->permissions()->sync($permissionIds);
            $this->command->info('Assigned ' . count($permissionIds) . ' permissions to Admin role.');
        }

        // User gets limited read-only permissions
        if ($userRole) {
            $userPermissions = PermissionEnum::forUser();
            $permissionIds = $allPermissions
                ->whereIn('name', $userPermissions)
                ->pluck('id')
                ->toArray();

            $userRole->permissions()->sync($permissionIds);
            $this->command->info('Assigned ' . count($permissionIds) . ' permissions to User role.');
        }
    }

    /**
     * Create super admin user if it doesn't exist.
     */
    protected function createSuperAdminUser(): void
    {
        $superAdminEmail = config('studio.super_admin_email', 'superadmin@app.com');

        $user = User::firstOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign super_admin role
        if (!$user->hasRole(Role::SUPER_ADMIN)) {
            $user->assignRole(Role::SUPER_ADMIN);
            $this->command->info("Created/updated super admin user: {$superAdminEmail}");
        }
    }
}
