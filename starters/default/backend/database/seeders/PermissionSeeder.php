<?php

namespace Database\Seeders;

use SavyApps\LaravelStudio\Database\Seeders\PermissionSeeder as BasePermissionSeeder;

/**
 * Permission Seeder that extends the core PermissionSeeder.
 *
 * This seeder handles permission syncing and role assignment. The core seeder
 * provides all the default behavior - extend this class only if you need
 * to customize the permission seeding for your application.
 *
 * Configuration is done via config/studio.php under 'seeder'.
 */
class PermissionSeeder extends BasePermissionSeeder
{
    // The parent class handles all default behavior:
    // - Syncs permissions from Permission enum
    // - Assigns permissions to roles (super_admin, admin, user)
    // - Creates super admin user (if configured)
    //
    // Override methods here if you need custom logic.
    //
    // Example: Add custom permissions after syncing
    //
    // public function run(): void
    // {
    //     parent::run();
    //
    //     // Add custom application-specific permissions
    //     \SavyApps\LaravelStudio\Models\Permission::firstOrCreate(
    //         ['name' => 'custom.feature'],
    //         [
    //             'display_name' => 'Access Custom Feature',
    //             'group' => 'custom',
    //             'description' => 'Allows access to custom feature',
    //         ]
    //     );
    // }
}
