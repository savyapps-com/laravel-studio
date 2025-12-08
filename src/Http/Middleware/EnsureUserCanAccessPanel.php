<?php

namespace SavyApps\LaravelStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SavyApps\LaravelStudio\Services\PanelService;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessPanel
{
    public function __construct(
        protected PanelService $panelService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  string  $panel  The panel key (e.g., 'admin', 'vendor', 'user')
     */
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->unauthorized($request, $panel);
        }

        $panelConfig = $this->panelService->getPanel($panel);

        if (!$panelConfig) {
            abort(404, "Panel not found: {$panel}");
        }

        // Check if user can access this panel based on their ROLE
        if (!$this->panelService->userCanAccessPanel($user, $panel)) {
            return $this->forbidden($request, $panel);
        }

        // Share panel context with the request
        $request->merge([
            '_panel' => $panel,
            '_panel_config' => $panelConfig,
        ]);

        // Also store in request attributes for easier access
        $request->attributes->set('panel', $panel);
        $request->attributes->set('panel_config', $panelConfig);

        return $next($request);
    }

    /**
     * Handle unauthorized (unauthenticated) access.
     */
    protected function unauthorized(Request $request, string $panel): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Handle forbidden (no permission) access.
     */
    protected function forbidden(Request $request, string $panel): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Access denied to this panel',
                'panel' => $panel,
            ], 403);
        }

        // Redirect to the panel they CAN access
        $redirectPanel = $this->findAccessiblePanel($request->user());
        if ($redirectPanel) {
            return redirect($redirectPanel['path']);
        }

        return redirect('/');
    }

    /**
     * Find the first accessible panel for the user.
     */
    protected function findAccessiblePanel($user): ?array
    {
        $defaultPanel = $this->panelService->getDefaultPanel($user);

        if ($defaultPanel) {
            $config = $this->panelService->getPanel($defaultPanel);
            return array_merge($config, ['key' => $defaultPanel]);
        }

        return null;
    }
}
