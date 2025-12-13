<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Support\Facades\Auth;

trait Authorizable
{
    /**
     * Check if authorization is enabled.
     */
    protected static function authorizationEnabled(): bool
    {
        return config('studio.authorization.enabled', true);
    }

    /**
     * Authorize an action, throw exception if denied.
     */
    public static function authorize(string $ability, $model = null): void
    {
        if (!static::authorizationEnabled()) {
            return;
        }

        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $allowed = match ($ability) {
            'viewAny', 'list' => static::canViewAny($user),
            'view' => static::canView($user, $model),
            'create' => static::canCreate($user),
            'update' => static::canUpdate($user, $model),
            'delete' => static::canDelete($user, $model),
            'bulkDelete' => static::canBulkDelete($user),
            'bulkUpdate' => static::canBulkUpdate($user),
            'runAction' => static::canRunAction($user, $model), // $model is action name here
            default => static::canCustomAction($user, $ability, $model),
        };

        if (!$allowed) {
            $abilityLabel = str($ability)->headline()->lower();
            abort(403, "You do not have permission to {$abilityLabel} this resource.");
        }
    }

    /**
     * Check authorization without throwing exception.
     */
    public static function can(string $ability, $model = null): bool
    {
        if (!static::authorizationEnabled()) {
            return true;
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return match ($ability) {
            'viewAny', 'list' => static::canViewAny($user),
            'view' => static::canView($user, $model),
            'create' => static::canCreate($user),
            'update' => static::canUpdate($user, $model),
            'delete' => static::canDelete($user, $model),
            'bulkDelete' => static::canBulkDelete($user),
            'bulkUpdate' => static::canBulkUpdate($user),
            'runAction' => static::canRunAction($user, $model), // $model is action name here
            default => static::canCustomAction($user, $ability, $model),
        };
    }

    /**
     * Can user see the resource list?
     * By default, checks for {resource}.list permission.
     */
    public static function canViewAny($user): bool
    {
        return static::checkPermission($user, static::key() . '.list');
    }

    /**
     * Can user view a specific record?
     * By default, checks for {resource}.view permission.
     */
    public static function canView($user, $model): bool
    {
        return static::checkPermission($user, static::key() . '.view');
    }

    /**
     * Can user create new records?
     * By default, checks for {resource}.create permission.
     */
    public static function canCreate($user): bool
    {
        return static::checkPermission($user, static::key() . '.create');
    }

    /**
     * Can user update a specific record?
     * By default, checks for {resource}.update permission.
     */
    public static function canUpdate($user, $model): bool
    {
        return static::checkPermission($user, static::key() . '.update');
    }

    /**
     * Can user delete a specific record?
     * By default, checks for {resource}.delete permission.
     */
    public static function canDelete($user, $model): bool
    {
        return static::checkPermission($user, static::key() . '.delete');
    }

    /**
     * Can user bulk delete records?
     * By default, checks for {resource}.delete permission.
     */
    public static function canBulkDelete($user): bool
    {
        return static::checkPermission($user, static::key() . '.delete');
    }

    /**
     * Can user bulk update records?
     * By default, checks for {resource}.update permission.
     */
    public static function canBulkUpdate($user): bool
    {
        return static::checkPermission($user, static::key() . '.update');
    }

    /**
     * Can user run a specific action?
     * By default, checks for {resource}.action.{actionName} or {resource}.update permission.
     */
    public static function canRunAction($user, ?string $actionName = null): bool
    {
        if ($actionName) {
            // First try specific action permission
            $actionPermission = static::key() . '.action.' . $actionName;
            if (static::checkPermission($user, $actionPermission)) {
                return true;
            }
        }

        // Fall back to update permission
        return static::checkPermission($user, static::key() . '.update');
    }

    /**
     * Can user run a custom action?
     * By default, checks for {resource}.{action} permission.
     */
    public static function canCustomAction($user, string $action, $model = null): bool
    {
        return static::checkPermission($user, static::key() . '.' . $action);
    }

    /**
     * Get permissions defined by this resource.
     * Override in Resource class to define custom permissions.
     */
    public static function permissions(): array
    {
        $resourceKey = static::key();
        $label = static::$label ?? str($resourceKey)->plural()->title()->toString();
        $singularLabel = static::$singularLabel ?? str($resourceKey)->singular()->title()->toString();

        return [
            "{$resourceKey}.list" => "View {$label} List",
            "{$resourceKey}.view" => "View {$singularLabel} Details",
            "{$resourceKey}.create" => "Create {$singularLabel}",
            "{$resourceKey}.update" => "Update {$singularLabel}",
            "{$resourceKey}.delete" => "Delete {$singularLabel}",
        ];
    }

    /**
     * Get the permission group name for this resource.
     * Used for organizing permissions in the UI.
     */
    public static function permissionGroup(): string
    {
        return static::$label ?? str(static::key())->plural()->title()->toString();
    }

    /**
     * Helper to check permission using user's hasPermission method.
     */
    protected static function checkPermission($user, string $permission): bool
    {
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        // Fallback: check if super admin
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return false;
    }
}
