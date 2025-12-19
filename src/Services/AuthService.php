<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    /**
     * Get the configured user model class.
     */
    protected function getUserModel(): string
    {
        return config('studio.authorization.models.user', 'App\\Models\\User');
    }

    /**
     * Authenticate a user with the provided credentials.
     */
    public function login(array $credentials, ?string $panelKey = null): array
    {
        // Extract remember flag if present (not used for API auth)
        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        // Extract panel if present in credentials
        $panel = $panelKey ?? $credentials['panel'] ?? null;
        unset($credentials['panel']);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // Check if user can access the requested panel
        if ($panel) {
            $panelService = app(PanelService::class);

            if (! $panelService->userCanAccessPanel($user, $panel)) {
                // Log out the user since they can't access this panel
                Auth::logout();

                // Return same error as invalid credentials to not reveal account exists
                throw ValidationException::withMessages([
                    'email' => ['The account does not exist.'],
                ]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Register a new user with the provided data.
     */
    public function register(array $userData, ?string $panelKey = 'admin'): array
    {
        // Get panel configuration and check if registration is allowed
        $panelConfig = $this->getPanelConfig($panelKey);

        if (! $panelConfig || ! $panelConfig['allow_registration']) {
            throw new AccessDeniedHttpException('Registration is disabled for this panel.');
        }

        $userModel = $this->getUserModel();
        $user = $userModel::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        // Assign the panel's default role if the user has role functionality
        $defaultRole = $panelConfig['default_role'] ?? 'user';
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($defaultRole);
        }

        event(new Registered($user));

        Auth::login($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Get panel configuration from database or config file.
     * For the admin panel, config file takes precedence (to allow env variable control).
     * For other panels, database takes precedence.
     */
    public function getPanelConfig(string $panelKey): ?array
    {
        // For admin panel, config takes precedence (to use ADMIN_ALLOW_REGISTRATION env)
        if ($panelKey === 'admin') {
            $configPanel = config("studio.panels.{$panelKey}");
            if ($configPanel) {
                return [
                    'allow_registration' => $configPanel['allow_registration'] ?? false,
                    'default_role' => $configPanel['default_role'] ?? 'admin',
                ];
            }
        }

        // For other panels, check database first
        $panel = DB::table('panels')
            ->where('key', $panelKey)
            ->where('is_active', true)
            ->first();

        if ($panel) {
            return [
                'allow_registration' => (bool) $panel->allow_registration,
                'default_role' => $panel->default_role,
            ];
        }

        // Fall back to config file for non-admin panels
        $configPanel = config("studio.panels.{$panelKey}");

        if ($configPanel) {
            return [
                'allow_registration' => $configPanel['allow_registration'] ?? false,
                'default_role' => $configPanel['default_role'] ?? $panelKey,
            ];
        }

        return null;
    }

    /**
     * Check if registration is allowed for a panel.
     */
    public function isRegistrationAllowed(string $panelKey): bool
    {
        $config = $this->getPanelConfig($panelKey);

        return $config && $config['allow_registration'];
    }

    /**
     * Log out the authenticated user and revoke their tokens.
     */
    public function logout(Authenticatable $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }

    /**
     * Send a password reset link to the provided email.
     * Stores the panel in cache for use by the User model's sendPasswordResetNotification.
     */
    public function sendPasswordResetLink(string $email, string $panel = 'admin'): void
    {
        // Store the panel in cache for 10 minutes (enough time for the email to be sent)
        // The User model will retrieve this when generating the reset URL
        Cache::put("password_reset_panel:{$email}", $panel, now()->addMinutes(10));

        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            // Clean up cache on failure
            Cache::forget("password_reset_panel:{$email}");

            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Reset the user's password with the provided data.
     */
    public function resetPassword(array $data): Authenticatable
    {
        $userModel = $this->getUserModel();

        $status = Password::reset(
            $data,
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $userModel::where('email', $data['email'])->first();
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(Authenticatable $user, array $data): Authenticatable
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $user->fresh();
    }

    /**
     * Change the user's password.
     */
    public function changePassword(Authenticatable $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Log out all sessions (revoke all tokens).
     */
    public function logoutAllSessions(Authenticatable $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }

    /**
     * Log out all sessions except the current one.
     */
    public function logoutOtherSessions(Authenticatable $user, string $currentTokenId): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }
    }

    /**
     * Check if an email exists in the system.
     */
    public function checkEmailExists(string $email): bool
    {
        $userModel = $this->getUserModel();

        return $userModel::where('email', $email)->exists();
    }
}
