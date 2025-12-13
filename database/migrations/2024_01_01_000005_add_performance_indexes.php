<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These indexes optimize common query patterns in Laravel Studio.
     */
    public function up(): void
    {
        // Add reverse index on permission_id for looking up roles by permission
        if (Schema::hasTable('role_permissions')) {
            if (!$this->indexExists('role_permissions', 'role_permissions_permission_id_index')) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->index('permission_id', 'role_permissions_permission_id_index');
                });
            }
        }

        // Add standalone created_at index for time-based queries without log_name
        if (Schema::hasTable('activities')) {
            if (!$this->indexExists('activities', 'activities_created_at_index')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->index('created_at', 'activities_created_at_index');
                });
            }

            // Add composite index for causer queries (user activity history)
            if (!$this->indexExists('activities', 'activities_causer_type_causer_id_created_at_index')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->index(['causer_type', 'causer_id', 'created_at'], 'activities_causer_type_causer_id_created_at_index');
                });
            }
        }

        // Add composite index for common panel queries (active panels ordered by priority)
        if (Schema::hasTable('panels')) {
            if (!$this->indexExists('panels', 'panels_is_active_priority_index')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->index(['is_active', 'priority'], 'panels_is_active_priority_index');
                });
            }

            // Add index for default panel lookup
            if (!$this->indexExists('panels', 'panels_is_default_index')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->index('is_default', 'panels_is_default_index');
                });
            }
        }

        // Add index for role lookup optimization (if roles table exists)
        if (Schema::hasTable('roles')) {
            if (!$this->indexExists('roles', 'roles_name_index') && !$this->indexExists('roles', 'roles_name_unique')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->index('name', 'roles_name_index');
                });
            }
        }

        // Add index for role_user table optimization (if exists)
        if (Schema::hasTable('role_user')) {
            if (!$this->indexExists('role_user', 'role_user_role_id_user_id_index')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->index(['role_id', 'user_id'], 'role_user_role_id_user_id_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            if ($this->indexExists('role_permissions', 'role_permissions_permission_id_index')) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->dropIndex('role_permissions_permission_id_index');
                });
            }
        }

        if (Schema::hasTable('activities')) {
            if ($this->indexExists('activities', 'activities_created_at_index')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->dropIndex('activities_created_at_index');
                });
            }
            if ($this->indexExists('activities', 'activities_causer_type_causer_id_created_at_index')) {
                Schema::table('activities', function (Blueprint $table) {
                    $table->dropIndex('activities_causer_type_causer_id_created_at_index');
                });
            }
        }

        if (Schema::hasTable('panels')) {
            if ($this->indexExists('panels', 'panels_is_active_priority_index')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->dropIndex('panels_is_active_priority_index');
                });
            }
            if ($this->indexExists('panels', 'panels_is_default_index')) {
                Schema::table('panels', function (Blueprint $table) {
                    $table->dropIndex('panels_is_default_index');
                });
            }
        }

        if (Schema::hasTable('roles')) {
            if ($this->indexExists('roles', 'roles_name_index')) {
                Schema::table('roles', function (Blueprint $table) {
                    $table->dropIndex('roles_name_index');
                });
            }
        }

        if (Schema::hasTable('role_user')) {
            if ($this->indexExists('role_user', 'role_user_role_id_user_id_index')) {
                Schema::table('role_user', function (Blueprint $table) {
                    $table->dropIndex('role_user_role_id_user_id_index');
                });
            }
        }
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                return count($indexes) > 0;
            }

            if ($driver === 'pgsql') {
                $indexes = DB::select(
                    "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                    [$table, $indexName]
                );
                return count($indexes) > 0;
            }

            if ($driver === 'sqlite') {
                $indexes = DB::select(
                    "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                    [$table, $indexName]
                );
                return count($indexes) > 0;
            }

            // For unknown drivers, try to add and catch if it fails
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};
