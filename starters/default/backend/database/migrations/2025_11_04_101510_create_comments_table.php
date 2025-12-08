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
        // Create comments table only if it doesn't exist
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->morphs('commentable'); // Already creates index on commentable_type and commentable_id
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('comment');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete();
                $table->index('parent_id');
            });
        } else {
            if (!Schema::hasColumn('comments', 'commentable_type')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->morphs('commentable');
                });
            }
            if (!Schema::hasColumn('comments', 'user_id')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete()->after('commentable_id');
                });
            }
            if (!Schema::hasColumn('comments', 'comment')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->text('comment')->after('user_id');
                });
            }
            if (!Schema::hasColumn('comments', 'parent_id')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('comment');
                });
            }
            if (!Schema::hasColumn('comments', 'created_at')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
            if (!Schema::hasColumn('comments', 'deleted_at')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->softDeletes();
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
