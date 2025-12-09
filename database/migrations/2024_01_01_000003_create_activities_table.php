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
        if (! Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->id();
                $table->string('log_name')->default('default');
                $table->string('event');
                $table->string('description')->nullable();
                $table->nullableMorphs('subject');
                $table->nullableMorphs('causer');
                $table->json('properties')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();

                $table->index(['log_name', 'created_at']);
                $table->index('event');
                $table->index('batch_uuid');
            });
        } else {
            if (! Schema::hasColumn('activities', 'log_name')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->string('log_name')->default('default')->after('id');
                });
            }
            if (! Schema::hasColumn('activities', 'event')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->string('event')->after('log_name');
                });
            }
            if (! Schema::hasColumn('activities', 'description')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->string('description')->nullable()->after('event');
                });
            }
            if (! Schema::hasColumn('activities', 'subject_type')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->nullableMorphs('subject');
                });
            }
            if (! Schema::hasColumn('activities', 'causer_type')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->nullableMorphs('causer');
                });
            }
            if (! Schema::hasColumn('activities', 'properties')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->json('properties')->nullable()->after('causer_id');
                });
            }
            if (! Schema::hasColumn('activities', 'ip_address')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->string('ip_address', 45)->nullable()->after('properties');
                });
            }
            if (! Schema::hasColumn('activities', 'user_agent')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                });
            }
            if (! Schema::hasColumn('activities', 'batch_uuid')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->uuid('batch_uuid')->nullable()->after('user_agent');
                });
            }
            if (! Schema::hasColumn('activities', 'created_at')) {
                Schema::table('activities', function (Blueprint $table) {
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
        Schema::dropIfExists('activities');
    }
};
