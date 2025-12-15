<?php

namespace SavyApps\LaravelStudio\Enums;

/**
 * Permission constants for Laravel Studio RBAC system.
 *
 * This enum-like class provides type-safe permission constants to prevent typos
 * and ensure consistency across the application. All permission checks should
 * use these constants instead of hardcoded strings.
 *
 * Permission naming convention: {resource}.{action}
 * - resource: lowercase plural (users, roles, settings)
 * - action: lowercase verb (list, view, create, update, delete)
 *
 * @example
 * use SavyApps\LaravelStudio\Enums\Permission;
 *
 * // Check permission
 * $user->hasPermission(Permission::USERS_CREATE);
 *
 * // In middleware
 * Route::middleware('permission:' . Permission::USERS_LIST);
 */
final class Permission
{
    // =========================================================================
    // USER MANAGEMENT PERMISSIONS
    // =========================================================================

    /** View the users list */
    public const USERS_LIST = 'users.list';

    /** View user details */
    public const USERS_VIEW = 'users.view';

    /** Create new users */
    public const USERS_CREATE = 'users.create';

    /** Update existing users */
    public const USERS_UPDATE = 'users.update';

    /** Delete users */
    public const USERS_DELETE = 'users.delete';

    /** Update user email address */
    public const USERS_UPDATE_EMAIL = 'users.update.email';

    /** Update user password */
    public const USERS_UPDATE_PASSWORD = 'users.update.password';

    /** Assign/change user roles */
    public const USERS_UPDATE_ROLES = 'users.update.roles';

    /** Impersonate other users */
    public const USERS_IMPERSONATE = 'users.impersonate';

    /** Export users data */
    public const USERS_EXPORT = 'users.export';

    /** Bulk delete users */
    public const USERS_BULK_DELETE = 'users.bulk.delete';

    /** Bulk update users */
    public const USERS_BULK_UPDATE = 'users.bulk.update';

    // =========================================================================
    // ROLE MANAGEMENT PERMISSIONS
    // =========================================================================

    /** View the roles list */
    public const ROLES_LIST = 'roles.list';

    /** View role details */
    public const ROLES_VIEW = 'roles.view';

    /** Create new roles */
    public const ROLES_CREATE = 'roles.create';

    /** Update existing roles */
    public const ROLES_UPDATE = 'roles.update';

    /** Delete roles */
    public const ROLES_DELETE = 'roles.delete';

    /** Assign roles to users */
    public const ROLES_ASSIGN = 'roles.assign';

    // =========================================================================
    // PERMISSION MANAGEMENT PERMISSIONS
    // =========================================================================

    /** View permissions list */
    public const PERMISSIONS_VIEW = 'permissions.view';

    /** Manage (assign/revoke) permissions */
    public const PERMISSIONS_MANAGE = 'permissions.manage';

    /** Sync permissions from resources */
    public const PERMISSIONS_SYNC = 'permissions.sync';

    // =========================================================================
    // SETTINGS PERMISSIONS
    // =========================================================================

    /** View settings list */
    public const SETTINGS_LIST = 'settings.list';

    /** View settings */
    public const SETTINGS_VIEW = 'settings.view';

    /** Update settings */
    public const SETTINGS_UPDATE = 'settings.update';

    /** Update system settings */
    public const SETTINGS_UPDATE_SYSTEM = 'settings.update.system';

    /** Update mail settings */
    public const SETTINGS_UPDATE_MAIL = 'settings.update.mail';

    // =========================================================================
    // ACTIVITY LOG PERMISSIONS
    // =========================================================================

    /** View activity log list */
    public const ACTIVITIES_LIST = 'activities.list';

    /** View activity details */
    public const ACTIVITIES_VIEW = 'activities.view';

    /** Delete activity logs */
    public const ACTIVITIES_DELETE = 'activities.delete';

    /** Export activity logs */
    public const ACTIVITIES_EXPORT = 'activities.export';

    // =========================================================================
    // PANEL ACCESS PERMISSIONS
    // =========================================================================

    /** Access the admin panel */
    public const PANEL_ADMIN_ACCESS = 'panel.admin.access';

    // =========================================================================
    // MEDIA MANAGEMENT PERMISSIONS
    // =========================================================================

    /** View media list */
    public const MEDIA_LIST = 'media.list';

    /** View media details */
    public const MEDIA_VIEW = 'media.view';

    /** Upload media */
    public const MEDIA_CREATE = 'media.create';

    /** Update media */
    public const MEDIA_UPDATE = 'media.update';

    /** Delete media */
    public const MEDIA_DELETE = 'media.delete';

    // =========================================================================
    // NOTIFICATION PERMISSIONS
    // =========================================================================

    /** View notifications list */
    public const NOTIFICATIONS_LIST = 'notifications.list';

    /** View notification templates */
    public const NOTIFICATIONS_VIEW = 'notifications.view';

    /** Create notification templates */
    public const NOTIFICATIONS_CREATE = 'notifications.create';

    /** Update notification templates */
    public const NOTIFICATIONS_UPDATE = 'notifications.update';

    /** Delete notification templates */
    public const NOTIFICATIONS_DELETE = 'notifications.delete';

    /** Send notifications */
    public const NOTIFICATIONS_SEND = 'notifications.send';

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get all permissions with their display names.
     *
     * @return array<string, string> Permission name => Display name
     */
    public static function all(): array
    {
        return [
            // Users
            self::USERS_LIST => 'View Users List',
            self::USERS_VIEW => 'View User Details',
            self::USERS_CREATE => 'Create User',
            self::USERS_UPDATE => 'Update User',
            self::USERS_DELETE => 'Delete User',
            self::USERS_UPDATE_EMAIL => 'Update User Email',
            self::USERS_UPDATE_PASSWORD => 'Update User Password',
            self::USERS_UPDATE_ROLES => 'Update User Roles',
            self::USERS_IMPERSONATE => 'Impersonate User',
            self::USERS_EXPORT => 'Export Users',
            self::USERS_BULK_DELETE => 'Bulk Delete Users',
            self::USERS_BULK_UPDATE => 'Bulk Update Users',

            // Roles
            self::ROLES_LIST => 'View Roles List',
            self::ROLES_VIEW => 'View Role Details',
            self::ROLES_CREATE => 'Create Role',
            self::ROLES_UPDATE => 'Update Role',
            self::ROLES_DELETE => 'Delete Role',
            self::ROLES_ASSIGN => 'Assign Roles',

            // Permissions
            self::PERMISSIONS_VIEW => 'View Permissions',
            self::PERMISSIONS_MANAGE => 'Manage Permissions',
            self::PERMISSIONS_SYNC => 'Sync Permissions',

            // Settings
            self::SETTINGS_LIST => 'View Settings List',
            self::SETTINGS_VIEW => 'View Settings',
            self::SETTINGS_UPDATE => 'Update Settings',
            self::SETTINGS_UPDATE_SYSTEM => 'Update System Settings',
            self::SETTINGS_UPDATE_MAIL => 'Update Mail Settings',

            // Activities
            self::ACTIVITIES_LIST => 'View Activity Log',
            self::ACTIVITIES_VIEW => 'View Activity Details',
            self::ACTIVITIES_DELETE => 'Delete Activity Logs',
            self::ACTIVITIES_EXPORT => 'Export Activity Logs',

            // Panel
            self::PANEL_ADMIN_ACCESS => 'Access Admin Panel',

            // Media
            self::MEDIA_LIST => 'View Media List',
            self::MEDIA_VIEW => 'View Media Details',
            self::MEDIA_CREATE => 'Upload Media',
            self::MEDIA_UPDATE => 'Update Media',
            self::MEDIA_DELETE => 'Delete Media',

            // Notifications
            self::NOTIFICATIONS_LIST => 'View Notifications List',
            self::NOTIFICATIONS_VIEW => 'View Notification Templates',
            self::NOTIFICATIONS_CREATE => 'Create Notification Template',
            self::NOTIFICATIONS_UPDATE => 'Update Notification Template',
            self::NOTIFICATIONS_DELETE => 'Delete Notification Template',
            self::NOTIFICATIONS_SEND => 'Send Notifications',
        ];
    }

    /**
     * Get permissions grouped by category.
     *
     * @return array<string, array<string, string>> Group name => [Permission name => Display name]
     */
    public static function grouped(): array
    {
        return [
            'Users' => [
                self::USERS_LIST => 'View Users List',
                self::USERS_VIEW => 'View User Details',
                self::USERS_CREATE => 'Create User',
                self::USERS_UPDATE => 'Update User',
                self::USERS_DELETE => 'Delete User',
                self::USERS_UPDATE_EMAIL => 'Update User Email',
                self::USERS_UPDATE_PASSWORD => 'Update User Password',
                self::USERS_UPDATE_ROLES => 'Update User Roles',
                self::USERS_IMPERSONATE => 'Impersonate User',
                self::USERS_EXPORT => 'Export Users',
                self::USERS_BULK_DELETE => 'Bulk Delete Users',
                self::USERS_BULK_UPDATE => 'Bulk Update Users',
            ],
            'Roles' => [
                self::ROLES_LIST => 'View Roles List',
                self::ROLES_VIEW => 'View Role Details',
                self::ROLES_CREATE => 'Create Role',
                self::ROLES_UPDATE => 'Update Role',
                self::ROLES_DELETE => 'Delete Role',
                self::ROLES_ASSIGN => 'Assign Roles',
            ],
            'Permissions' => [
                self::PERMISSIONS_VIEW => 'View Permissions',
                self::PERMISSIONS_MANAGE => 'Manage Permissions',
                self::PERMISSIONS_SYNC => 'Sync Permissions',
            ],
            'Settings' => [
                self::SETTINGS_LIST => 'View Settings List',
                self::SETTINGS_VIEW => 'View Settings',
                self::SETTINGS_UPDATE => 'Update Settings',
                self::SETTINGS_UPDATE_SYSTEM => 'Update System Settings',
                self::SETTINGS_UPDATE_MAIL => 'Update Mail Settings',
            ],
            'Activities' => [
                self::ACTIVITIES_LIST => 'View Activity Log',
                self::ACTIVITIES_VIEW => 'View Activity Details',
                self::ACTIVITIES_DELETE => 'Delete Activity Logs',
                self::ACTIVITIES_EXPORT => 'Export Activity Logs',
            ],
            'Panel' => [
                self::PANEL_ADMIN_ACCESS => 'Access Admin Panel',
            ],
            'Media' => [
                self::MEDIA_LIST => 'View Media List',
                self::MEDIA_VIEW => 'View Media Details',
                self::MEDIA_CREATE => 'Upload Media',
                self::MEDIA_UPDATE => 'Update Media',
                self::MEDIA_DELETE => 'Delete Media',
            ],
            'Notifications' => [
                self::NOTIFICATIONS_LIST => 'View Notifications List',
                self::NOTIFICATIONS_VIEW => 'View Notification Templates',
                self::NOTIFICATIONS_CREATE => 'Create Notification Template',
                self::NOTIFICATIONS_UPDATE => 'Update Notification Template',
                self::NOTIFICATIONS_DELETE => 'Delete Notification Template',
                self::NOTIFICATIONS_SEND => 'Send Notifications',
            ],
        ];
    }

    /**
     * Get all permission names as a flat array.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /**
     * Check if a permission name is valid.
     *
     * @param string $permission The permission name to validate
     * @return bool
     */
    public static function isValid(string $permission): bool
    {
        return in_array($permission, self::names(), true);
    }

    /**
     * Get permissions for a specific resource.
     *
     * @param string $resource The resource name (e.g., 'users', 'roles')
     * @return array<string, string> Permission name => Display name
     */
    public static function forResource(string $resource): array
    {
        $prefix = $resource . '.';

        return array_filter(
            self::all(),
            fn(string $key) => str_starts_with($key, $prefix),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get the group name for a permission.
     *
     * @param string $permission The permission name
     * @return string|null The group name or null if not found
     */
    public static function getGroup(string $permission): ?string
    {
        foreach (self::grouped() as $group => $permissions) {
            if (array_key_exists($permission, $permissions)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Get the display name for a permission.
     *
     * @param string $permission The permission name
     * @return string The display name or the permission name if not found
     */
    public static function getDisplayName(string $permission): string
    {
        return self::all()[$permission] ?? $permission;
    }

    /**
     * Get permissions for super admin role.
     * Super admin gets ALL permissions.
     *
     * @return array<string>
     */
    public static function forSuperAdmin(): array
    {
        return self::names();
    }

    /**
     * Get permissions for admin role.
     * Admin gets all permissions except permission management.
     *
     * @return array<string>
     */
    public static function forAdmin(): array
    {
        return array_values(array_filter(
            self::names(),
            fn(string $permission) => !in_array($permission, [
                self::PERMISSIONS_MANAGE,
                self::PERMISSIONS_SYNC,
            ], true)
        ));
    }

    /**
     * Get permissions for regular user role.
     * Users get limited read-only permissions.
     *
     * @return array<string>
     */
    public static function forUser(): array
    {
        return [
            self::USERS_VIEW, // View own profile
        ];
    }

    /**
     * Build a permission name from resource and action.
     *
     * @param string $resource The resource name
     * @param string $action The action name
     * @return string The permission name
     */
    public static function build(string $resource, string $action): string
    {
        return "{$resource}.{$action}";
    }

    /**
     * Parse a permission name into resource and action.
     *
     * @param string $permission The permission name
     * @return array{resource: string, action: string}|null
     */
    public static function parse(string $permission): ?array
    {
        $parts = explode('.', $permission, 2);

        if (count($parts) !== 2) {
            return null;
        }

        return [
            'resource' => $parts[0],
            'action' => $parts[1],
        ];
    }
}
