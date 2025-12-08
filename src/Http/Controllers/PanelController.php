<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Services\PanelService;

class PanelController extends Controller
{
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
            ])->values(),
            'default' => $this->panelService->getDefaultPanel(),
        ]);
    }

    /**
     * Get configuration for a specific panel.
     */
    public function show(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return response()->json(['message' => 'Panel not found'], 404);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return response()->json(['message' => 'Access denied'], 403);
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
        ]);
    }

    /**
     * Get menu items for a specific panel.
     */
    public function menu(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return response()->json(['message' => 'Panel not found'], 404);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return response()->json([
            'menu' => $this->panelService->getPanelMenu($panel),
        ]);
    }

    /**
     * Get resources available in a specific panel.
     */
    public function resources(Request $request, string $panel): JsonResponse
    {
        $config = $this->panelService->getPanel($panel);

        if (!$config) {
            return response()->json(['message' => 'Panel not found'], 404);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return response()->json(['message' => 'Access denied'], 403);
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
            return response()->json(['message' => 'Panel not found'], 404);
        }

        if (!$this->panelService->userCanAccessPanel($request->user(), $panel)) {
            return response()->json([
                'message' => 'Access denied',
                'available_panels' => collect($this->panelService->getAccessiblePanels())
                    ->map(fn($config, $key) => [
                        'key' => $key,
                        'label' => $config['label'],
                        'path' => $config['path'],
                    ])->values(),
            ], 403);
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
        ]);
    }
}
