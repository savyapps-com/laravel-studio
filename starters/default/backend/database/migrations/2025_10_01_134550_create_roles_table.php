<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: The roles table is created by the package migration.
     * This migration ensures the table exists and adds any app-specific customizations.
     */
    public function up(): void
    {
        // The roles table is created by the package (2024_01_01_000001_5_create_roles_table)
        // This migration is kept for backwards compatibility and app-specific customizations
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the roles table here as it may be managed by the package
        // Only drop if you're sure this is an app-only table
    }
};
