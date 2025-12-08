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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->default('default');
            $table->string('event');                    // created, updated, deleted, custom
            $table->string('description')->nullable();
            $table->nullableMorphs('subject');          // The model being acted on
            $table->nullableMorphs('causer');           // The user performing the action
            $table->json('properties')->nullable();     // old/new values, custom data
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('batch_uuid')->nullable();     // Group related activities
            $table->timestamps();

            // Indexes for common queries
            // Note: nullableMorphs already creates indexes on subject and causer
            $table->index(['log_name', 'created_at']);
            $table->index('event');
            $table->index('batch_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
