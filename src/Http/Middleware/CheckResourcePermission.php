<?php

namespace SavyApps\LaravelStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SavyApps\LaravelStudio\Enums\Permission as PermissionEnum;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user has a specific permission.
 *
 * Usage in routes:
 *   Route::get('/users', ...)->middleware('permission:users.list');
 *   Route::post('/users', ...)->middleware('permission:' . Permission::USERS_CREATE);
 *
 * Features:
 * - Respects global RBAC toggle
 * - Supports JSON and HTML responses
 * - Super admin bypass (via hasPermission method)
 */
class CheckResourcePermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permission The permission to check
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // If RBAC is disabled, allow all
        if (!config('studio.authorization.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated',
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        // Check if user has the permission
        $hasPermission = $this->checkPermission($user, $permission);

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                    'required_permission' => $permission,
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    /**
     * Check if user has the given permission.
     *
     * @param mixed $user
     * @param string $permission
     * @return bool
     */
    protected function checkPermission($user, string $permission): bool
    {
        // Use hasPermission method if available (includes super admin bypass)
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
