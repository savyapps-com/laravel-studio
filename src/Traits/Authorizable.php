<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait Authorizable
{
    /**
     * The policy class for this resource.
     * Set this property in your Resource class to use Laravel Policies.
     *
     * Example: public static string $policy = \App\Policies\UserPolicy::class;
     */
    // public static string $policy;

    /**
     * Check if authorization is enabled.
     */
    protected static function authorizationEnabled(): bool
    {
        return config('studio.authorization.enabled', true);
    }

    /**
     * Get the policy instance for this resource.
     * Returns null if no policy is defined.
     */
    protected static function getPolicy(): ?object
    {
        if (property_exists(static::class, 'policy') && !empty(static::$policy)) {
            $policyClass = static::$policy;
            if (class_exists($policyClass)) {
                return app($policyClass);
            }
        }

        return null;
    }

    /**
     * Check authorization using policy first, then fall back to permissions.
     */
    protected static function authorizeViaPolicy($user, string $ability, $model = null): ?bool
    {
        $policy = static::getPolicy();

        if (!$policy) {
            return null; // No policy, use permission-based check
        }

        // Map our abilities to standard policy methods
        $policyMethod = match ($ability) {
            'viewAny', 'list' => 'viewAny',
            'view' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'bulkDelete' => 'deleteAny',
            'bulkUpdate' => 'updateAny',
            'restore' => 'restore',
            'forceDelete' => 'forceDelete',
            default => $ability, // Custom abilities use their name
        };

        // Check if policy has this method
        if (!method_exists($policy, $policyMethod)) {
            return null; // Method not in policy, fall back to permissions
        }

        // Call policy method with appropriate arguments
        if (in_array($policyMethod, ['viewAny', 'create', 'deleteAny', 'updateAny'])) {
            return $policy->$policyMethod($user);
        }

        return $policy->$policyMethod($user, $model);
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

        // Check policy first (returns null if no policy or method)
        $policyResult = static::authorizeViaPolicy($user, $ability, $model);
        if ($policyResult !== null) {
            if (!$policyResult) {
                $abilityLabel = str($ability)->headline()->lower();
                abort(403, "You do not have permission to {$abilityLabel} this resource.");
            }
            return;
        }

        // Fall back to permission-based authorization
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

        // Check policy first (returns null if no policy or method)
        $policyResult = static::authorizeViaPolicy($user, $ability, $model);
        if ($policyResult !== null) {
            return $policyResult;
        }

        // Fall back to permission-based authorization
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
