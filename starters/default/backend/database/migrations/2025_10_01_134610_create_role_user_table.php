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

                // Ensure a user can only have one role
                $table->unique('user_id');
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
        // manually drop tables or create drop logic if needed
    }
};
