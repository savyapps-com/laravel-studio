<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use SavyApps\LaravelStudio\Models\Panel;
use SavyApps\LaravelStudio\Services\PanelService;

class PanelManagementController extends Controller
{
    public function __construct(
        protected PanelService $panelService
    ) {}

    /**
     * List all panels (including inactive).
     */
    public function index(): JsonResponse
    {
        $panels = $this->panelService->getAllPanelsFromDatabase();

        return response()->json([
            'data' => $panels,
            'meta' => [
                'total' => count($panels),
            ],
        ]);
    }

    /**
     * Create a new panel.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:panels,key|alpha_dash',
            'label' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:255',
            'middleware' => 'nullable|array',
            'middleware.*' => 'string|max:255',
            'resources' => 'nullable|array',
            'resources.*' => 'string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'menu' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Ensure path starts with /
        if (! str_starts_with($data['path'], '/')) {
            $data['path'] = '/'.$data['path'];
        }

        $panel = $this->panelService->createPanel($data);

        // If this is set as default, unset other defaults
        if ($panel->is_default) {
            $panel->setAsDefault();
        }

        return response()->json([
            'message' => 'Panel created successfully',
            'data' => $panel->toArray(),
        ], 201);
    }

    /**
     * Get a specific panel.
     */
    public function show(string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        return response()->json([
            'data' => $panel->toArray(),
        ]);
    }

    /**
     * Update a panel.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|string|max:255|alpha_dash|unique:panels,key,'.$panel->id,
            'label' => 'sometimes|string|max:255',
            'path' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:255',
            'middleware' => 'nullable|array',
            'middleware.*' => 'string|max:255',
            'resources' => 'nullable|array',
            'resources.*' => 'string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'menu' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Ensure path starts with /
        if (isset($data['path']) && ! str_starts_with($data['path'], '/')) {
            $data['path'] = '/'.$data['path'];
        }

        $updatedPanel = $this->panelService->updatePanel($key, $data);

        // If this is set as default, unset other defaults
        if ($updatedPanel && $updatedPanel->is_default) {
            $updatedPanel->setAsDefault();
        }

        return response()->json([
            'message' => 'Panel updated successfully',
            'data' => $updatedPanel->toArray(),
        ]);
    }

    /**
     * Delete a panel.
     */
    public function destroy(string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        // Prevent deletion of the last active panel
        $activeCount = Panel::active()->count();
        if ($panel->is_active && $activeCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last active panel',
            ], 422);
        }

        $this->panelService->deletePanel($key);

        return response()->json([
            'message' => 'Panel deleted successfully',
        ]);
    }

    /**
     * Toggle panel active status.
     */
    public function toggle(string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        // Prevent deactivating the last active panel
        if ($panel->is_active) {
            $activeCount = Panel::active()->count();
            if ($activeCount <= 1) {
                return response()->json([
                    'message' => 'Cannot deactivate the last active panel',
                ], 422);
            }
        }

        $toggledPanel = $this->panelService->togglePanel($key);

        return response()->json([
            'message' => $toggledPanel->is_active ? 'Panel activated' : 'Panel deactivated',
            'data' => $toggledPanel->toArray(),
        ]);
    }

    /**
     * Duplicate a panel.
     */
    public function duplicate(Request $request, string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:panels,key|alpha_dash',
            'label' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $panel->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);

        $data['key'] = $request->input('key');
        $data['label'] = $request->input('label', $panel->label.' (Copy)');
        $data['path'] = '/'.$request->input('key');
        $data['is_default'] = false;

        $newPanel = $this->panelService->createPanel($data);

        return response()->json([
            'message' => 'Panel duplicated successfully',
            'data' => $newPanel->toArray(),
        ], 201);
    }

    /**
     * Set a panel as default.
     */
    public function setDefault(string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        if (! $panel->is_active) {
            return response()->json([
                'message' => 'Cannot set inactive panel as default',
            ], 422);
        }

        $panel->setAsDefault();

        return response()->json([
            'message' => 'Panel set as default',
            'data' => $panel->fresh()->toArray(),
        ]);
    }

    /**
     * Update panel resources.
     */
    public function updateResources(Request $request, string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'resources' => 'required|array',
            'resources.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $panel->update(['resources' => $request->input('resources')]);
        $this->panelService->clearCache();

        return response()->json([
            'message' => 'Panel resources updated',
            'data' => $panel->fresh()->toArray(),
        ]);
    }

    /**
     * Update panel menu.
     */
    public function updateMenu(Request $request, string $key): JsonResponse
    {
        $panel = Panel::findByKey($key);

        if (! $panel) {
            return response()->json([
                'message' => 'Panel not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'menu' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $panel->update(['menu' => $request->input('menu')]);
        $this->panelService->clearCache();

        return response()->json([
            'message' => 'Panel menu updated',
            'data' => $panel->fresh()->toArray(),
        ]);
    }

    /**
     * Get available resources that can be added to panels.
     */
    public function availableResources(): JsonResponse
    {
        $resources = config('studio.resources', []);

        return response()->json([
            'data' => collect($resources)->map(function ($config, $key) {
                return [
                    'key' => $key,
                    'label' => $config['label'] ?? str($key)->title()->toString(),
                    'icon' => $config['icon'] ?? 'folder',
                ];
            })->values()->toArray(),
        ]);
    }

    /**
     * Get available features that can be added to panels.
     */
    public function availableFeatures(): JsonResponse
    {
        $features = config('studio.features', []);

        return response()->json([
            'data' => collect($features)->map(function ($config, $key) {
                return [
                    'key' => $key,
                    'label' => $config['label'] ?? str($key)->title()->toString(),
                    'icon' => $config['icon'] ?? 'star',
                ];
            })->values()->toArray(),
        ]);
    }
}
