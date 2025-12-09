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
        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->primary(['role_id', 'permission_id']);
            });
        } else {
            if (! Schema::hasColumn('role_permissions', 'role_id')) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                });
            }
            if (! Schema::hasColumn('role_permissions', 'permission_id')) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
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
