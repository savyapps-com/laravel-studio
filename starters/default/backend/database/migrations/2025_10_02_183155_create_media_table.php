<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create media table only if it doesn't exist
        if (!Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();

                $table->morphs('model');
                $table->uuid()->nullable()->unique();
                $table->string('collection_name');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk');
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size');
                $table->json('manipulations');
                $table->json('custom_properties');
                $table->json('generated_conversions');
                $table->json('responsive_images');
                $table->unsignedInteger('order_column')->nullable()->index();

                $table->nullableTimestamps();
            });
        } else {
            if (!Schema::hasColumn('media', 'model_type')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->morphs('model');
                });
            }
            if (!Schema::hasColumn('media', 'uuid')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->uuid()->nullable()->unique()->after('model_id');
                });
            }
            if (!Schema::hasColumn('media', 'collection_name')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->string('collection_name')->after('uuid');
                });
            }
            if (!Schema::hasColumn('media', 'name')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->string('name')->after('collection_name');
                });
            }
            if (!Schema::hasColumn('media', 'file_name')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->string('file_name')->after('name');
                });
            }
            if (!Schema::hasColumn('media', 'mime_type')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->string('mime_type')->nullable()->after('file_name');
                });
            }
            if (!Schema::hasColumn('media', 'disk')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->string('disk')->after('mime_type');
                });
            }
            if (!Schema::hasColumn('media', 'conversions_disk')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->string('conversions_disk')->nullable()->after('disk');
                });
            }
            if (!Schema::hasColumn('media', 'size')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->unsignedBigInteger('size')->after('conversions_disk');
                });
            }
            if (!Schema::hasColumn('media', 'manipulations')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->json('manipulations')->after('size');
                });
            }
            if (!Schema::hasColumn('media', 'custom_properties')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->json('custom_properties')->after('manipulations');
                });
            }
            if (!Schema::hasColumn('media', 'generated_conversions')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->json('generated_conversions')->after('custom_properties');
                });
            }
            if (!Schema::hasColumn('media', 'responsive_images')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->json('responsive_images')->after('generated_conversions');
                });
            }
            if (!Schema::hasColumn('media', 'order_column')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->unsignedInteger('order_column')->nullable()->index()->after('responsive_images');
                });
            }
            if (!Schema::hasColumn('media', 'created_at')) {
                Schema::table('media', function (Blueprint $table) {
                    $table->nullableTimestamps();
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
