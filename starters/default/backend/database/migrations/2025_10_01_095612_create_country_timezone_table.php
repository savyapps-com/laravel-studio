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
        // Create country_timezone table only if it doesn't exist
        if (! Schema::hasTable('country_timezone')) {
            Schema::create('country_timezone', function (Blueprint $table) {
                $table->id();
                $table->foreignId('country_id')->constrained()->onDelete('cascade');
                $table->foreignId('timezone_id')->constrained()->onDelete('cascade');
                $table->boolean('is_primary')->default(false);
                $table->json('regions')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['country_id', 'timezone_id']);
                $table->index('is_primary');
            });
        } else {
            if (! Schema::hasColumn('country_timezone', 'country_id')) {
                Schema::table('country_timezone', function (Blueprint $table) {
                    $table->foreignId('country_id')->constrained()->onDelete('cascade')->after('id');
                });
            }
            if (! Schema::hasColumn('country_timezone', 'timezone_id')) {
                Schema::table('country_timezone', function (Blueprint $table) {
                    $table->foreignId('timezone_id')->constrained()->onDelete('cascade')->after('country_id');
                });
            }
            if (! Schema::hasColumn('country_timezone', 'is_primary')) {
                Schema::table('country_timezone', function (Blueprint $table) {
                    $table->boolean('is_primary')->default(false)->after('timezone_id');
                });
            }
            if (! Schema::hasColumn('country_timezone', 'regions')) {
                Schema::table('country_timezone', function (Blueprint $table) {
                    $table->json('regions')->nullable()->after('is_primary');
                });
            }
            if (! Schema::hasColumn('country_timezone', 'notes')) {
                Schema::table('country_timezone', function (Blueprint $table) {
                    $table->text('notes')->nullable()->after('regions');
                });
            }
            if (! Schema::hasColumn('country_timezone', 'created_at')) {
                Schema::table('country_timezone', function (Blueprint $table) {
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
