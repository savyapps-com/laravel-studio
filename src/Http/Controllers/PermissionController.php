<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Models\Permission;
use SavyApps\LaravelStudio\Services\AuthorizationService;

class PermissionController extends Controller
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Get all permissions grouped by group name.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'grouped' => $this->authorizationService->getGroupedPermissions(),
            'permissions' => $this->authorizationService->getAllPermissions(),
        ]);
    }

    /**
     * Get permissions for a specific role.
     */
    public function rolePermissions(Request $request, int $roleId): JsonResponse
    {
        $roleModel = config('studio.authorization.models.role', \App\Models\Role::class);
        $role = $roleModel::findOrFail($roleId);

        return response()->json([
            'role_id' => $roleId,
            'role_name' => $role->name ?? null,
            'permissions' => $this->authorizationService->getRolePermissions($role),
        ]);
    }

    /**
     * Update permissions for a specific role.
     */
    public function updateRolePermissions(Request $request, int $roleId): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        $roleModel = config('studio.authorization.models.role', \App\Models\Role::class);
        $role = $roleModel::findOrFail($roleId);

        $this->authorizationService->syncRolePermissions($role, $request->input('permissions'));

        return response()->json([
            'message' => 'Permissions updated successfully',
            'role_id' => $roleId,
            'permissions' => $this->authorizationService->getRolePermissions($role),
        ]);
    }

    /**
     * Sync permissions from resources (API endpoint).
     */
    public function sync(): JsonResponse
    {
        $synced = $this->authorizationService->syncPermissions();

        return response()->json([
            'message' => 'Permissions synced successfully',
            'count' => count($synced),
            'permissions' => $synced,
        ]);
    }

    /**
     * Get current user's permissions.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $permissions = [];
        $isSuperAdmin = false;

        if (method_exists($user, 'getPermissionNames')) {
            $permissions = $user->getPermissionNames();
        }

        if (method_exists($user, 'isSuperAdmin')) {
            $isSuperAdmin = $user->isSuperAdmin();
        }

        return response()->json([
            'permissions' => $permissions,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }

    /**
     * Check if current user has specific permission(s).
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'permission' => 'required_without:permissions|string',
            'permissions' => 'required_without:permission|array',
            'permissions.*' => 'string',
            'mode' => 'in:any,all',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Single permission check
        if ($request->has('permission')) {
            $hasPermission = method_exists($user, 'hasPermission')
                ? $user->hasPermission($request->input('permission'))
                : false;

            return response()->json([
                'permission' => $request->input('permission'),
                'allowed' => $hasPermission,
            ]);
        }

        // Multiple permissions check
        $permissions = $request->input('permissions', []);
        $mode = $request->input('mode', 'any');

        $allowed = false;
        if (method_exists($user, 'hasAnyPermission') && method_exists($user, 'hasAllPermissions')) {
            $allowed = $mode === 'all'
                ? $user->hasAllPermissions($permissions)
                : $user->hasAnyPermission($permissions);
        }

        return response()->json([
            'permissions' => $permissions,
            'mode' => $mode,
            'allowed' => $allowed,
        ]);
    }
}
