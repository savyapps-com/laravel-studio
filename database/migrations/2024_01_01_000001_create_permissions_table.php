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
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->string('group')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('group');
            });
        } else {
            if (! Schema::hasColumn('permissions', 'name')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('name')->unique()->after('id');
                });
            }
            if (! Schema::hasColumn('permissions', 'display_name')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('display_name')->after('name');
                });
            }
            if (! Schema::hasColumn('permissions', 'group')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('group')->nullable()->after('display_name');
                });
            }
            if (! Schema::hasColumn('permissions', 'description')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->text('description')->nullable()->after('group');
                });
            }
            if (! Schema::hasColumn('permissions', 'created_at')) {
                Schema::table('permissions', function (Blueprint $table) {
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
        Schema::dropIfExists('permissions');
    }
};
