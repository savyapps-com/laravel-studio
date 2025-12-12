<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    /**
     * Authenticate a user with the provided credentials.
     */
    public function login(array $credentials): array
    {
        // Extract remember flag if present (not used for API auth)
        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
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

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        // Assign the panel's default role
        $defaultRole = $panelConfig['default_role'] ?? 'user';
        $user->assignRole($defaultRole);

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
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Send a password reset link to the provided email.
     */
    public function sendPasswordResetLink(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Reset the user's password with the provided data.
     */
    public function resetPassword(array $data): User
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
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

        return User::where('email', $data['email'])->first();
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(User $user, array $data): User
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
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Log out all sessions (revoke all tokens).
     */
    public function logoutAllSessions(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Log out all sessions except the current one.
     */
    public function logoutOtherSessions(User $user, string $currentTokenId): void
    {
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();
    }

    /**
     * Check if an email exists in the system.
     */
    public function checkEmailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
}
