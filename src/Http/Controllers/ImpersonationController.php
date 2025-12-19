<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Services\ImpersonationService;

class ImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonationService
    ) {}

    /**
     * Start impersonating a user.
     */
    public function impersonate(Request $request, int $userId): JsonResponse
    {
        $admin = $request->user();

        // Check if user can impersonate (must be admin or have impersonate permission)
        if (!$this->canImpersonate($admin)) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can impersonate users.',
            ], 403);
        }

        // Get the user model class from config
        $userModel = config('studio.authorization.models.user', 'App\\Models\\User');
        $targetUser = $userModel::findOrFail($userId);

        try {
            $this->impersonationService->impersonate($admin, $targetUser);

            return response()->json([
                'message' => 'Successfully impersonating user.',
                'user' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                ],
                'impersonation' => $this->impersonationService->getStatus(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Stop impersonating and return to admin account.
     */
    public function stopImpersonating(Request $request): JsonResponse
    {
        if (!$this->impersonationService->isImpersonating()) {
            return response()->json([
                'message' => 'Not currently impersonating.',
            ], 400);
        }

        try {
            $this->impersonationService->stopImpersonating();

            return response()->json([
                'message' => 'Successfully stopped impersonating.',
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get impersonation status.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => $this->impersonationService->getStatus(),
        ]);
    }

    /**
     * Check if the user can impersonate others.
     */
    protected function canImpersonate($user): bool
    {
        // Check if impersonation is enabled in config
        if (!config('studio.auth.impersonation.enabled', true)) {
            return false;
        }

        // Check for isAdmin method (common pattern)
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // Check for isSuperAdmin method
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Check for hasPermission method
        if (method_exists($user, 'hasPermission') && $user->hasPermission('users.impersonate')) {
            return true;
        }

        // Check for hasRole method with super_admin or admin
        if (method_exists($user, 'hasRole')) {
            $superAdminRole = config('studio.authorization.super_admin_role', 'super_admin');
            if ($user->hasRole($superAdminRole) || $user->hasRole('admin')) {
                return true;
            }
        }

        return false;
    }
}
