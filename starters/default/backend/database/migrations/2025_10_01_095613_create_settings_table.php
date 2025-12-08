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
        // Create settings table only if it doesn't exist
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key');
                $table->text('value'); // Changed from json to text for manual JSON encoding/decoding
                $table->enum('type', ['string', 'integer', 'boolean', 'array', 'json', 'reference']);
                $table->string('group');
                $table->enum('scope', ['global', 'user', 'admin'])->default('global');
                $table->string('icon')->nullable();
                $table->string('label');
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(true);
                $table->boolean('is_encrypted')->default(false);
                $table->json('validation_rules')->nullable();
                $table->nullableMorphs('settable');
                $table->nullableMorphs('referenceable');
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->unique(['key', 'scope', 'settable_type', 'settable_id']);
                $table->index(['group', 'scope']);
            });
        } else {
            if (!Schema::hasColumn('settings', 'key')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('key')->after('id');
                });
            }
            if (!Schema::hasColumn('settings', 'value')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->text('value')->after('key');
                });
            }
            if (!Schema::hasColumn('settings', 'type')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->enum('type', ['string', 'integer', 'boolean', 'array', 'json', 'reference'])->after('value');
                });
            }
            if (!Schema::hasColumn('settings', 'group')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('group')->after('type');
                });
            }
            if (!Schema::hasColumn('settings', 'scope')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->enum('scope', ['global', 'user', 'admin'])->default('global')->after('group');
                });
            }
            if (!Schema::hasColumn('settings', 'icon')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('icon')->nullable()->after('scope');
                });
            }
            if (!Schema::hasColumn('settings', 'label')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->string('label')->after('icon');
                });
            }
            if (!Schema::hasColumn('settings', 'description')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->text('description')->nullable()->after('label');
                });
            }
            if (!Schema::hasColumn('settings', 'is_public')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->boolean('is_public')->default(true)->after('description');
                });
            }
            if (!Schema::hasColumn('settings', 'is_encrypted')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->boolean('is_encrypted')->default(false)->after('is_public');
                });
            }
            if (!Schema::hasColumn('settings', 'validation_rules')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->json('validation_rules')->nullable()->after('is_encrypted');
                });
            }
            if (!Schema::hasColumn('settings', 'settable_type')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->nullableMorphs('settable');
                });
            }
            if (!Schema::hasColumn('settings', 'referenceable_type')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->nullableMorphs('referenceable');
                });
            }
            if (!Schema::hasColumn('settings', 'order')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->integer('order')->default(0)->after('referenceable_id');
                });
            }
            if (!Schema::hasColumn('settings', 'created_at')) {
                Schema::table('settings', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Seed immediately after table creation for production deployments (skip in testing)
        if (! app()->environment('testing')) {
            Artisan::call('db:seed', [
                '--class' => 'SettingsSeeder',
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
