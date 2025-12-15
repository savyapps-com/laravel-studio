<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the role_user pivot table for the many-to-many relationship
     * between users and roles.
     */
    public function up(): void
    {
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                // Prevent duplicate role assignments
                $table->unique(['role_id', 'user_id']);
            });
        } else {
            // Add missing columns if table exists
            if (!Schema::hasColumn('role_user', 'role_id')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->foreignId('role_id')->constrained()->cascadeOnDelete()->after('id');
                });
            }
            if (!Schema::hasColumn('role_user', 'user_id')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete()->after('role_id');
                });
            }
            if (!Schema::hasColumn('role_user', 'created_at')) {
                Schema::table('role_user', function (Blueprint $table) {
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
        Schema::dropIfExists('role_user');
    }
};
