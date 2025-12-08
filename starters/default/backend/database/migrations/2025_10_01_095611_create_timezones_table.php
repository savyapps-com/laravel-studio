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
        // Create timezones table only if it doesn't exist
        if (!Schema::hasTable('timezones')) {
            Schema::create('timezones', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('abbreviation')->nullable();
                $table->string('abbreviation_dst')->nullable();
                $table->integer('offset');
                $table->integer('offset_dst')->nullable();
                $table->string('offset_formatted');
                $table->boolean('uses_dst')->default(false);
                $table->string('display_name');
                $table->string('city_name')->nullable();
                $table->string('region');
                $table->json('coordinates')->nullable();
                $table->bigInteger('population')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(999);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('region');
                $table->index(['is_active', 'is_primary']);
                $table->index('uses_dst');
            });
        } else {
            if (!Schema::hasColumn('timezones', 'name')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('name')->unique()->after('id');
                });
            }
            if (!Schema::hasColumn('timezones', 'abbreviation')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('abbreviation')->nullable()->after('name');
                });
            }
            if (!Schema::hasColumn('timezones', 'abbreviation_dst')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('abbreviation_dst')->nullable()->after('abbreviation');
                });
            }
            if (!Schema::hasColumn('timezones', 'offset')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->integer('offset')->after('abbreviation_dst');
                });
            }
            if (!Schema::hasColumn('timezones', 'offset_dst')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->integer('offset_dst')->nullable()->after('offset');
                });
            }
            if (!Schema::hasColumn('timezones', 'offset_formatted')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('offset_formatted')->after('offset_dst');
                });
            }
            if (!Schema::hasColumn('timezones', 'uses_dst')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->boolean('uses_dst')->default(false)->after('offset_formatted');
                });
            }
            if (!Schema::hasColumn('timezones', 'display_name')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('display_name')->after('uses_dst');
                });
            }
            if (!Schema::hasColumn('timezones', 'city_name')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('city_name')->nullable()->after('display_name');
                });
            }
            if (!Schema::hasColumn('timezones', 'region')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->string('region')->after('city_name');
                });
            }
            if (!Schema::hasColumn('timezones', 'coordinates')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->json('coordinates')->nullable()->after('region');
                });
            }
            if (!Schema::hasColumn('timezones', 'population')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->bigInteger('population')->nullable()->after('coordinates');
                });
            }
            if (!Schema::hasColumn('timezones', 'is_primary')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->boolean('is_primary')->default(false)->after('population');
                });
            }
            if (!Schema::hasColumn('timezones', 'is_active')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('is_primary');
                });
            }
            if (!Schema::hasColumn('timezones', 'display_order')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->integer('display_order')->default(999)->after('is_active');
                });
            }
            if (!Schema::hasColumn('timezones', 'metadata')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->json('metadata')->nullable()->after('display_order');
                });
            }
            if (!Schema::hasColumn('timezones', 'created_at')) {
                Schema::table('timezones', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Seed immediately after table creation for production deployments (skip in testing)
        if (! app()->environment('testing')) {
            Artisan::call('db:seed', [
                '--class' => 'TimezonesSeeder',
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
