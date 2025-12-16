<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed reference data first
        $this->call([
            SettingListsSeeder::class,
            EmailTemplatesSeeder::class,
        ]);

        // Seed permissions and roles first (creates roles and super admin user)
        $this->call([
            PermissionSeeder::class,
        ]);

        // Create default users with roles (roles must exist before this runs)
        $users = [
            'admin' => 'admin',
            'user' => 'user',
        ];

        foreach ($users as $name => $roleSlug) {
            $user = User::firstOrCreate(
                ['email' => "{$name}@app.com"],
                [
                    'name' => ucfirst($name),
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role to user (if not already assigned)
            if (! $user->hasRole($roleSlug)) {
                $user->assignRole($roleSlug);
            }
        }
    }
}
