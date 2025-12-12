<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $panelKey = null): Response
    {
        // Get panel key from route parameter or middleware parameter
        $panelKey = $panelKey ?? $request->route('panel');

        if (! $panelKey) {
            return $this->unauthorized($request);
        }

        // Check if panel exists and is active
        $panel = DB::table('panels')
            ->where('key', $panelKey)
            ->where('is_active', true)
            ->first();

        if (! $panel) {
            return $this->notFound($request);
        }

        // Check if user is authenticated
        if (! $request->user()) {
            if (! $request->expectsJson()) {
                return redirect()->route('login');
            }

            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has required role for this panel
        $panelRoles = json_decode($panel->roles, true) ?? [];

        if (! empty($panelRoles)) {
            $userRoles = $request->user()->roles->pluck('key')->toArray();
            $hasRole = ! empty(array_intersect($panelRoles, $userRoles));

            if (! $hasRole) {
                return $this->forbidden($request);
            }
        }

        // Store panel info in request for later use
        $request->attributes->set('panel', $panel);

        return $next($request);
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorized(Request $request): Response
    {
        if (! $request->expectsJson()) {
            return redirect()->route('login');
        }

        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    /**
     * Return not found response.
     */
    protected function notFound(Request $request): Response
    {
        if (! $request->expectsJson()) {
            abort(404);
        }

        return response()->json(['message' => 'Panel not found'], 404);
    }

    /**
     * Return forbidden response.
     */
    protected function forbidden(Request $request): Response
    {
        if (! $request->expectsJson()) {
            abort(403);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
