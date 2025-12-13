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
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        } else {
            if (! Schema::hasColumn('jobs', 'queue')) {
                Schema::table('jobs', function (Blueprint $table) {
                    $table->string('queue')->index()->after('id');
                });
            }
            if (! Schema::hasColumn('jobs', 'payload')) {
                Schema::table('jobs', function (Blueprint $table) {
                    $table->longText('payload')->after('queue');
                });
            }
            if (! Schema::hasColumn('jobs', 'attempts')) {
                Schema::table('jobs', function (Blueprint $table) {
                    $table->unsignedTinyInteger('attempts')->after('payload');
                });
            }
            if (! Schema::hasColumn('jobs', 'reserved_at')) {
                Schema::table('jobs', function (Blueprint $table) {
                    $table->unsignedInteger('reserved_at')->nullable()->after('attempts');
                });
            }
            if (! Schema::hasColumn('jobs', 'available_at')) {
                Schema::table('jobs', function (Blueprint $table) {
                    $table->unsignedInteger('available_at')->after('reserved_at');
                });
            }
            if (! Schema::hasColumn('jobs', 'created_at')) {
                Schema::table('jobs', function (Blueprint $table) {
                    $table->unsignedInteger('created_at')->after('available_at');
                });
            }
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        } else {
            if (! Schema::hasColumn('job_batches', 'id')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->string('id')->primary()->first();
                });
            }
            if (! Schema::hasColumn('job_batches', 'name')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->string('name')->after('id');
                });
            }
            if (! Schema::hasColumn('job_batches', 'total_jobs')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->integer('total_jobs')->after('name');
                });
            }
            if (! Schema::hasColumn('job_batches', 'pending_jobs')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->integer('pending_jobs')->after('total_jobs');
                });
            }
            if (! Schema::hasColumn('job_batches', 'failed_jobs')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->integer('failed_jobs')->after('pending_jobs');
                });
            }
            if (! Schema::hasColumn('job_batches', 'failed_job_ids')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->longText('failed_job_ids')->after('failed_jobs');
                });
            }
            if (! Schema::hasColumn('job_batches', 'options')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->mediumText('options')->nullable()->after('failed_job_ids');
                });
            }
            if (! Schema::hasColumn('job_batches', 'cancelled_at')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->integer('cancelled_at')->nullable()->after('options');
                });
            }
            if (! Schema::hasColumn('job_batches', 'created_at')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->integer('created_at')->after('cancelled_at');
                });
            }
            if (! Schema::hasColumn('job_batches', 'finished_at')) {
                Schema::table('job_batches', function (Blueprint $table) {
                    $table->integer('finished_at')->nullable()->after('created_at');
                });
            }
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        } else {
            if (! Schema::hasColumn('failed_jobs', 'uuid')) {
                Schema::table('failed_jobs', function (Blueprint $table) {
                    $table->string('uuid')->unique()->after('id');
                });
            }
            if (! Schema::hasColumn('failed_jobs', 'connection')) {
                Schema::table('failed_jobs', function (Blueprint $table) {
                    $table->text('connection')->after('uuid');
                });
            }
            if (! Schema::hasColumn('failed_jobs', 'queue')) {
                Schema::table('failed_jobs', function (Blueprint $table) {
                    $table->text('queue')->after('connection');
                });
            }
            if (! Schema::hasColumn('failed_jobs', 'payload')) {
                Schema::table('failed_jobs', function (Blueprint $table) {
                    $table->longText('payload')->after('queue');
                });
            }
            if (! Schema::hasColumn('failed_jobs', 'exception')) {
                Schema::table('failed_jobs', function (Blueprint $table) {
                    $table->longText('exception')->after('payload');
                });
            }
            if (! Schema::hasColumn('failed_jobs', 'failed_at')) {
                Schema::table('failed_jobs', function (Blueprint $table) {
                    $table->timestamp('failed_at')->useCurrent()->after('exception');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
