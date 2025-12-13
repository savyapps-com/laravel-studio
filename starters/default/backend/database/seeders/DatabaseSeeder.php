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

        $users = ['admin', 'user'];

        foreach ($users as $role) {
            $user = User::firstOrCreate(
                ['email' => "{$role}@app.com"],
                [
                    'name' => ucfirst($role),
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role to user (if not already assigned)
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
