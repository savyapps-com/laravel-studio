<?php

use App\Enums\Status;
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
        // Create users table only if it doesn't exist
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                // create column only if it doesn't exist
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('status')->default(Status::default()->value);
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            if (! Schema::hasColumn('users', 'name')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('name')->after('id');
                });
            }
            if (! Schema::hasColumn('users', 'email')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('email')->after('name');
                });
            }
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                });
            }
            if (! Schema::hasColumn('users', 'status')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('status')->default(Status::default()->value)->after('email_verified_at');
                });
            }
            if (! Schema::hasColumn('users', 'password')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('password')->after('status');
                });
            }
            if (! Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->rememberToken()->after('password');
                });
            }
            if (! Schema::hasColumn('users', 'created_at')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->timestamps();
                });
            }
        }

        // Create password_reset_tokens table only if it doesn't exist
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        } else {
            if (! Schema::hasColumn('password_reset_tokens', 'email')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->string('email')->primary()->first();
                });
            }
            if (! Schema::hasColumn('password_reset_tokens', 'token')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->string('token')->after('email');
                });
            }
            if (! Schema::hasColumn('password_reset_tokens', 'created_at')) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->timestamp('created_at')->nullable()->after('token');
                });
            }
        }

        // Create sessions table only if it doesn't exist
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        } else {
            if (! Schema::hasColumn('sessions', 'id')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->string('id')->primary()->first();
                });
            }
            if (! Schema::hasColumn('sessions', 'user_id')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->index()->after('id');
                });
            }
            if (! Schema::hasColumn('sessions', 'ip_address')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->string('ip_address', 45)->nullable()->after('user_id');
                });
            }
            if (! Schema::hasColumn('sessions', 'user_agent')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->text('user_agent')->nullable()->after('ip_address');
                });
            }
            if (! Schema::hasColumn('sessions', 'payload')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->longText('payload')->after('user_agent');
                });
            }
            if (! Schema::hasColumn('sessions', 'last_activity')) {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->integer('last_activity')->index()->after('payload');
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
