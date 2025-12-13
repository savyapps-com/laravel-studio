<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('slug');
            });
        } else {
            // Add missing columns if table exists
            if (! Schema::hasColumn('roles', 'name')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->string('name')->unique()->after('id');
                });
            }
            if (! Schema::hasColumn('roles', 'slug')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->string('slug')->unique()->after('name');
                });
            }
            if (! Schema::hasColumn('roles', 'description')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->text('description')->nullable()->after('slug');
                });
            }
            if (! Schema::hasColumn('roles', 'created_at')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Seed default roles with idempotent check
        $this->seedDefaultRoles();
    }

    /**
     * Seed default roles if they don't exist.
     */
    protected function seedDefaultRoles(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with full access to all features',
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Regular user with standard access',
            ],
        ];

        foreach ($roles as $role) {
            if (! DB::table('roles')->where('slug', $role['slug'])->exists()) {
                DB::table('roles')->insert([
                    ...$role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
