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
        // Create role_user table only if it doesn't exist
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                // Allow users to have multiple roles, but prevent duplicate assignments
                $table->unique(['role_id', 'user_id']);
            });
        } else {
            if (! Schema::hasColumn('role_user', 'role_id')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->foreignId('role_id')->constrained()->onDelete('cascade')->after('id');
                });
            }
            if (! Schema::hasColumn('role_user', 'user_id')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->foreignId('user_id')->constrained()->onDelete('cascade')->after('role_id');
                });
            }
            if (! Schema::hasColumn('role_user', 'created_at')) {
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
