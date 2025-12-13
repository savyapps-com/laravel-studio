<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Services\PanelService;
use SavyApps\LaravelStudio\Traits\ApiResponse;

class PanelController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PanelService $panelService
    ) {}

    /**
     * Get all panels the current user can access (based on their roles).
     */
    public function index(Request $request): JsonResponse
    {
        $panels = $this->panelService->getAccessiblePanels();

        return response()->json([
            'panels' => collect($panels)->map(fn($config, $key) => [
                'key' => $key,
                'label' => $config['label'],
                'path' => $config['path'],
                'icon' => $config['icon'],
                'allow_registration' => $config['allow_registration'] ?? false,
                'default_role' => $config['default_role'] ?? null,
            ])->values(),
            'default' => $this->panelService->getDefaultPanel(),
        ]);
    }

    /**
     * Get public panel information (for login/register pages - no auth required).
     */
    public function info(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return $this->notFoundResponse('Panel', $panel);
        }

        // Get allow_registration from database panel config (database takes precedence)
        $allowRegistration = $config['allow_registration'] ?? false;

        return response()->json([
            'panel' => $panel,
            'label' => $config['label'],
            'icon' => $config['icon'],
            'allow_registration' => $allowRegistration,
        ]);
    }

    /**
     * Get configuration for a specific panel.
     */
    public function show(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return $this->notFoundResponse('Panel', $panel);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return $this->forbiddenResponse('Access denied to this panel');
        }

        return response()->json([
            'panel' => $panel,
            'label' => $config['label'],
            'path' => $config['path'],
            'icon' => $config['icon'],
            'menu' => $this->panelService->getPanelMenu($panel),
            'resources' => array_keys($this->panelService->getPanelResources($panel)),
            'features' => $config['features'] ?? [],
            'settings' => $config['settings'] ?? [],
            'allow_registration' => $config['allow_registration'] ?? false,
            'default_role' => $config['default_role'] ?? null,
        ]);
    }

    /**
     * Get menu items for a specific panel.
     */
    public function menu(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return $this->notFoundResponse('Panel', $panel);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return $this->forbiddenResponse('Access denied to this panel');
        }

        return $this->successResponse(['menu' => $this->panelService->getPanelMenu($panel)]);
    }

    /**
     * Get resources available in a specific panel.
     */
    public function resources(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return $this->notFoundResponse('Panel', $panel);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return $this->forbiddenResponse('Access denied to this panel');
        }

        $resources = $this->panelService->getPanelResources($panel);

        return response()->json([
            'resources' => collect($resources)->map(fn($config, $key) => [
                'key' => $key,
                'label' => $config['label'] ?? str($key)->title()->toString(),
                'icon' => $config['icon'] ?? 'folder',
            ])->values(),
        ]);
    }

    /**
     * Switch to a different panel.
     * Returns the new panel configuration if the user has access.
     */
    public function switch(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return $this->notFoundResponse('Panel', $panel);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return $this->errorResponse(
                'Access denied',
                403,
                [
                    'available_panels' => collect($this->panelService->getAccessiblePanels())
                        ->map(fn($config, $key) => [
                            'key' => $key,
                            'label' => $config['label'],
                            'path' => $config['path'],
                        ])->values(),
                ],
                'FORBIDDEN'
            );
        }

        return response()->json([
            'panel' => $panel,
            'label' => $config['label'],
            'path' => $config['path'],
            'icon' => $config['icon'],
            'menu' => $this->panelService->getPanelMenu($panel),
            'resources' => array_keys($this->panelService->getPanelResources($panel)),
            'features' => $config['features'] ?? [],
            'settings' => $config['settings'] ?? [],
            'allow_registration' => $config['allow_registration'] ?? false,
            'default_role' => $config['default_role'] ?? null,
        ]);
    }
}
