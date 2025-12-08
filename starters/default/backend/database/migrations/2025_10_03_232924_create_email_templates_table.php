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
        // Create email_templates table only if it doesn't exist
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique()->comment('Unique identifier like user_welcome, password_reset');
                $table->string('name')->comment('Human-readable name');
                $table->text('subject_template')->comment('Blade template for subject');
                $table->longText('body_content')->comment('Complete Blade template with full HTML');
                $table->text('preview_thumbnail')->nullable()->comment('Cached preview thumbnail');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'key']);
            });
        } else {
            if (!Schema::hasColumn('email_templates', 'key')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->string('key')->unique()->comment('Unique identifier like user_welcome, password_reset')->after('id');
                });
            }
            if (!Schema::hasColumn('email_templates', 'name')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->string('name')->comment('Human-readable name')->after('key');
                });
            }
            if (!Schema::hasColumn('email_templates', 'subject_template')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->text('subject_template')->comment('Blade template for subject')->after('name');
                });
            }
            if (!Schema::hasColumn('email_templates', 'body_content')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->longText('body_content')->comment('Complete Blade template with full HTML')->after('subject_template');
                });
            }
            if (!Schema::hasColumn('email_templates', 'preview_thumbnail')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->text('preview_thumbnail')->nullable()->comment('Cached preview thumbnail')->after('body_content');
                });
            }
            if (!Schema::hasColumn('email_templates', 'is_active')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('preview_thumbnail');
                });
            }
            if (!Schema::hasColumn('email_templates', 'created_by')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_active');
                });
            }
            if (!Schema::hasColumn('email_templates', 'updated_by')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
                });
            }
            if (!Schema::hasColumn('email_templates', 'created_at')) {
                Schema::table('email_templates', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Seed default email templates
        (new \Database\Seeders\EmailTemplatesSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // manually drop tables or create drop logic if needed
    }
};
