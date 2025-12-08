<?php

namespace SavyApps\LaravelStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckResourcePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission  The permission to check
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!config('studio.authorization.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        // Check if user has the permission
        $hasPermission = false;

        if (method_exists($user, 'hasPermission')) {
            $hasPermission = $user->hasPermission($permission);
        } elseif (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $hasPermission = true;
        }

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
}
