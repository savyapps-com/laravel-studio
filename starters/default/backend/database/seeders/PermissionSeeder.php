<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use SavyApps\LaravelStudio\Models\Permission;
use SavyApps\LaravelStudio\Services\AuthorizationService;

class PermissionSeeder extends Seeder
{
    /**
     * Seed permissions and assign them to roles.
     */
    public function run(): void
    {
        // First, sync permissions from resources
        $this->syncPermissions();

        // Then assign permissions to roles
        $this->assignPermissionsToRoles();

        // Create super admin user if it doesn't exist
        $this->createSuperAdminUser();
    }

    /**
     * Sync permissions from registered resources.
     */
    protected function syncPermissions(): void
    {
        try {
            $authService = app(AuthorizationService::class);
            $synced = $authService->syncPermissions();

            $this->command->info('Synced ' . count($synced) . ' permissions from resources.');
        } catch (\Exception $e) {
            $this->command->warn('Could not sync permissions from resources: ' . $e->getMessage());

            // Fall back to creating base permissions manually
            $this->createBasePermissions();
        }
    }

    /**
     * Create base permissions if resource sync fails.
     */
    protected function createBasePermissions(): void
    {
        $basePermissions = [
            // User permissions
            ['name' => 'users.list', 'display_name' => 'View Users List', 'group' => 'Users'],
            ['name' => 'users.view', 'display_name' => 'View User Details', 'group' => 'Users'],
            ['name' => 'users.create', 'display_name' => 'Create User', 'group' => 'Users'],
            ['name' => 'users.update', 'display_name' => 'Update User', 'group' => 'Users'],
            ['name' => 'users.delete', 'display_name' => 'Delete User', 'group' => 'Users'],

            // Role permissions
            ['name' => 'roles.list', 'display_name' => 'View Roles List', 'group' => 'Roles'],
            ['name' => 'roles.view', 'display_name' => 'View Role Details', 'group' => 'Roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Role', 'group' => 'Roles'],
            ['name' => 'roles.update', 'display_name' => 'Update Role', 'group' => 'Roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Role', 'group' => 'Roles'],

            // Permission management
            ['name' => 'permissions.view', 'display_name' => 'View Permissions', 'group' => 'Permissions'],
            ['name' => 'permissions.manage', 'display_name' => 'Manage Permissions', 'group' => 'Permissions'],

            // Settings permissions
            ['name' => 'settings.list', 'display_name' => 'View Settings', 'group' => 'Settings'],
            ['name' => 'settings.update', 'display_name' => 'Update Settings', 'group' => 'Settings'],

            // Activity log permissions
            ['name' => 'activities.list', 'display_name' => 'View Activity Log', 'group' => 'Activities'],
            ['name' => 'activities.view', 'display_name' => 'View Activity Details', 'group' => 'Activities'],
        ];

        foreach ($basePermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'group' => $permission['group'],
                    'description' => null,
                ]
            );
        }

        $this->command->info('Created ' . count($basePermissions) . ' base permissions.');
    }

    /**
     * Assign permissions to roles.
     */
    protected function assignPermissionsToRoles(): void
    {
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            $this->command->warn('No permissions found to assign.');

            return;
        }

        // Get roles
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $userRole = Role::where('slug', 'user')->first();

        // Super admin gets all permissions (though they bypass checks anyway)
        if ($superAdminRole) {
            $superAdminRole->permissions()->sync($allPermissions->pluck('id')->toArray());
            $this->command->info('Assigned all permissions to Super Admin role.');
        }

        // Admin gets all permissions except permission management
        if ($adminRole) {
            $adminPermissions = $allPermissions->filter(function ($permission) {
                // Admin can do everything except manage permissions
                return $permission->name !== 'permissions.manage';
            });

            $adminRole->permissions()->sync($adminPermissions->pluck('id')->toArray());
            $this->command->info('Assigned ' . $adminPermissions->count() . ' permissions to Admin role.');
        }

        // User gets limited read-only permissions
        if ($userRole) {
            $userPermissions = $allPermissions->filter(function ($permission) {
                // Users can only view their own profile
                return in_array($permission->name, [
                    'users.view', // View own profile
                ]);
            });

            $userRole->permissions()->sync($userPermissions->pluck('id')->toArray());
            $this->command->info('Assigned ' . $userPermissions->count() . ' permissions to User role.');
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
        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
            $this->command->info("Created/updated super admin user: {$superAdminEmail}");
        }
    }
}
