<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Http\Requests\ChangePasswordRequest;
use SavyApps\LaravelStudio\Http\Requests\ForgotPasswordRequest;
use SavyApps\LaravelStudio\Http\Requests\LoginRequest;
use SavyApps\LaravelStudio\Http\Requests\RegisterRequest;
use SavyApps\LaravelStudio\Http\Requests\ResetPasswordRequest;
use SavyApps\LaravelStudio\Http\Requests\UpdateProfileRequest;
use SavyApps\LaravelStudio\Services\AuthService;
use SavyApps\LaravelStudio\Services\ImpersonationService;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected ImpersonationService $impersonationService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $panelKey = $request->input('panel', 'admin');
        $result = $this->authService->register($request->validated(), $panelKey);
        $result['user']->load('roles');

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $this->formatUser($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Check if registration is allowed for a panel.
     */
    public function checkRegistration(Request $request): JsonResponse
    {
        $panelKey = $request->input('panel', 'admin');
        $allowed = $this->authService->isRegistrationAllowed($panelKey);

        return response()->json([
            'panel' => $panelKey,
            'allow_registration' => $allowed,
        ]);
    }

    /**
     * Login a user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());
        $result['user']->load('roles');

        // Get impersonation status
        $impersonationStatus = $this->impersonationService->getStatus();

        // Get user settings if settings service is available
        $settings = $this->getUserSettings($result['user']);

        $response = [
            'message' => 'Login successful.',
            'user' => $this->formatUser($result['user']),
            'token' => $result['token'],
            'impersonation' => $impersonationStatus,
        ];

        if ($settings !== null) {
            $response['settings'] = $settings;
        }

        return response()->json($response);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles');

        // Get impersonation status
        $impersonationStatus = $this->impersonationService->getStatus();

        // Get user settings if settings service is available
        $settings = $this->getUserSettings($user);

        $response = [
            'user' => $this->formatUser($user),
            'impersonation' => $impersonationStatus,
        ];

        if ($settings !== null) {
            $response['settings'] = $settings;
        }

        return response()->json($response);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $panel = $request->validated('panel') ?? 'admin';

        $this->authService->sendPasswordResetLink($email, $panel);

        return response()->json([
            'message' => 'Password reset link sent to your email.',
        ]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resetPassword($request->validated());

        return response()->json([
            'message' => 'Password reset successfully.',
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());
        $user->load('roles');

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Change user password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword($request->user(), $request->validated('password'));

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Logout all sessions (revoke all tokens).
     */
    public function logoutAllSessions(Request $request): JsonResponse
    {
        $this->authService->logoutAllSessions($request->user());

        return response()->json([
            'message' => 'Logged out from all sessions successfully.',
        ]);
    }

    /**
     * Logout all sessions except the current one.
     */
    public function logoutOtherSessions(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $this->authService->logoutOtherSessions($request->user(), $currentTokenId);

        return response()->json([
            'message' => 'Logged out from all other sessions successfully.',
        ]);
    }

    /**
     * Check if an email exists in the system (public endpoint for invitation flow).
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = $this->authService->checkEmailExists($request->input('email'));

        return response()->json([
            'exists' => $exists,
            'email' => $request->input('email'),
        ]);
    }

    /**
     * Format user data for API response.
     * Can be overridden in application by extending this controller.
     */
    protected function formatUser($user): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        // Include roles if loaded
        if ($user->relationLoaded('roles')) {
            $data['roles'] = $user->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug ?? $role->name,
            ])->toArray();
        }

        // Include admin panel access flag
        if (isset($user->can_access_admin_panel)) {
            $data['can_access_admin_panel'] = (bool) $user->can_access_admin_panel;
        }

        // Include avatar if available
        if (method_exists($user, 'getFirstMediaUrl')) {
            $data['avatar'] = $user->getFirstMediaUrl('avatar', 'thumb') ?: null;
        }

        return $data;
    }

    /**
     * Get user settings if a settings service is available.
     * Applications can bind their own settings service to 'studio.settings'.
     */
    protected function getUserSettings($user): ?array
    {
        // Check if application has bound a settings service
        if (app()->bound('studio.settings')) {
            $settingsService = app('studio.settings');
            if (method_exists($settingsService, 'getForUser')) {
                return $settingsService->getForUser($user);
            }
        }

        return null;
    }
}
