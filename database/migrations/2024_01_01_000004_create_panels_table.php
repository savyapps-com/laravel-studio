<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('panels')) {
            Schema::create('panels', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label');
                $table->string('path');
                $table->string('icon')->default('layout');
                $table->string('role')->nullable();
                $table->json('roles')->nullable();
                $table->json('middleware')->nullable();
                $table->json('resources')->nullable();
                $table->json('features')->nullable();
                $table->json('menu')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->integer('priority')->default(100);
                $table->timestamps();

                $table->index('is_active');
                $table->index('priority');
            });
        } else {
            if (! Schema::hasColumn('panels', 'key')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->string('key')->unique()->after('id');
                });
            }
            if (! Schema::hasColumn('panels', 'label')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->string('label')->after('key');
                });
            }
            if (! Schema::hasColumn('panels', 'path')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->string('path')->after('label');
                });
            }
            if (! Schema::hasColumn('panels', 'icon')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->string('icon')->default('layout')->after('path');
                });
            }
            if (! Schema::hasColumn('panels', 'role')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->string('role')->nullable()->after('icon');
                });
            }
            if (! Schema::hasColumn('panels', 'roles')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->json('roles')->nullable()->after('role');
                });
            }
            if (! Schema::hasColumn('panels', 'middleware')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->json('middleware')->nullable()->after('roles');
                });
            }
            if (! Schema::hasColumn('panels', 'resources')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->json('resources')->nullable()->after('middleware');
                });
            }
            if (! Schema::hasColumn('panels', 'features')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->json('features')->nullable()->after('resources');
                });
            }
            if (! Schema::hasColumn('panels', 'menu')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->json('menu')->nullable()->after('features');
                });
            }
            if (! Schema::hasColumn('panels', 'settings')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->json('settings')->nullable()->after('menu');
                });
            }
            if (! Schema::hasColumn('panels', 'is_active')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('settings');
                });
            }
            if (! Schema::hasColumn('panels', 'is_default')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->boolean('is_default')->default(false)->after('is_active');
                });
            }
            if (! Schema::hasColumn('panels', 'priority')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->integer('priority')->default(100)->after('is_default');
                });
            }
            if (! Schema::hasColumn('panels', 'created_at')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
