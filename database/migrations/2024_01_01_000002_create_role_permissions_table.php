<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the role_permissions pivot table for the many-to-many relationship
     * between roles and permissions.
     */
    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['role_id', 'permission_id']);
            });
        } else {
            if (!Schema::hasColumn('role_permissions', 'role_id')) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                });
            }
            if (!Schema::hasColumn('role_permissions', 'permission_id')) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                });
            }
            if (!Schema::hasColumn('role_permissions', 'created_at')) {
                Schema::table('role_permissions', function (Blueprint $table) {
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
        Schema::dropIfExists('role_permissions');
    }
};
