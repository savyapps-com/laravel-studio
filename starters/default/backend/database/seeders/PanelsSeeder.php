<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PanelsSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            [
                'key' => 'admin',
                'label' => 'Admin Panel',
                'path' => '/admin',
                'icon' => 'layout',
                'role' => 'admin',
                'roles' => json_encode(['admin']),
                'middleware' => json_encode(['admin']),
                'resources' => json_encode(['users', 'roles', 'countries', 'timezones']),
                'features' => json_encode(['email-templates', 'system-settings']),
                'menu' => json_encode([
                    ['type' => 'link', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
                    ['type' => 'divider'],
                    ['type' => 'header', 'label' => 'Management'],
                    ['type' => 'resource', 'resource' => 'users'],
                    ['type' => 'resource', 'resource' => 'roles'],
                    ['type' => 'divider'],
                    ['type' => 'header', 'label' => 'System'],
                    ['type' => 'resource', 'resource' => 'countries'],
                    ['type' => 'resource', 'resource' => 'timezones'],
                    ['type' => 'feature', 'feature' => 'email-templates'],
                    ['type' => 'feature', 'feature' => 'system-settings'],
                ]),
                'settings' => json_encode([
                    'layout' => 'classic',
                    'theme' => 'light',
                ]),
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($panels as $panel) {
            // Only insert if panel doesn't exist
            if (DB::table('panels')->where('key', $panel['key'])->doesntExist()) {
                DB::table('panels')->insert($panel);
            }
        }
    }
}
