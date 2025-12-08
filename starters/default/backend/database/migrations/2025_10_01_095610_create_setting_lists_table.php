<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create setting_lists table only if it doesn't exist
        if (!Schema::hasTable('setting_lists')) {
            Schema::create('setting_lists', function (Blueprint $table) {
                $table->id();
                $table->string('key')->index();
                $table->string('label');
                $table->string('value');
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('setting_lists', 'key')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->string('key')->index()->after('id');
                });
            }
            if (!Schema::hasColumn('setting_lists', 'label')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->string('label')->after('key');
                });
            }
            if (!Schema::hasColumn('setting_lists', 'value')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->string('value')->after('label');
                });
            }
            if (!Schema::hasColumn('setting_lists', 'metadata')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->json('metadata')->nullable()->after('value');
                });
            }
            if (!Schema::hasColumn('setting_lists', 'is_active')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('metadata');
                });
            }
            if (!Schema::hasColumn('setting_lists', 'order')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->integer('order')->default(0)->after('is_active');
                });
            }
            if (!Schema::hasColumn('setting_lists', 'created_at')) {
                Schema::table('setting_lists', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Seed immediately after table creation for production deployments (skip in testing)
        if (! app()->environment('testing')) {
            Artisan::call('db:seed', [
                '--class' => 'SettingListsSeeder',
                '--force' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // manually drop tables or create drop logic if needed
    }
};
