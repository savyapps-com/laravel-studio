<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    /**
     * Get the configured user model class.
     */
    protected function getUserModel(): string
    {
        return config('studio.authorization.models.user', 'App\\Models\\User');
    }

    /**
     * Start impersonating a user.
     *
     * @param  Authenticatable  $admin  The admin user initiating the impersonation
     * @param  Authenticatable  $targetUser  The user to impersonate
     *
     * @throws \Exception If impersonation is not allowed
     */
    public function impersonate(Authenticatable $admin, Authenticatable $targetUser): bool
    {
        // Check if admin can impersonate (must have isAdmin method or super_admin role)
        if (! $this->canImpersonate($admin)) {
            throw new \Exception('Only administrators can impersonate users.');
        }

        // Prevent impersonating other admins
        if ($this->isAdmin($targetUser) && $targetUser->id !== $admin->id) {
            throw new \Exception('Cannot impersonate other administrators.');
        }

        // Store the admin's ID in an encrypted session variable
        $impersonationData = [
            'admin_id' => $admin->id,
            'started_at' => now()->toDateTimeString(),
        ];

        // Store impersonation data in session
        Session::put('impersonating', Crypt::encryptString(json_encode($impersonationData)));

        // Switch authentication to target user
        // For session-based auth (web guard)
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            Auth::guard('web')->login($targetUser);
        }

        // Regenerate session to prevent session fixation
        Session::regenerate();

        // Re-store the impersonation data after session regeneration
        Session::put('impersonating', Crypt::encryptString(json_encode($impersonationData)));

        return true;
    }

    /**
     * Stop impersonating and return to admin account.
     */
    public function stopImpersonating(): bool
    {
        if (! $this->isImpersonating()) {
            return false;
        }

        $impersonationData = $this->getImpersonationData();
        $adminId = $impersonationData['admin_id'];

        // Find the original admin user
        $userModel = $this->getUserModel();
        $admin = $userModel::findOrFail($adminId);

        // Clear impersonation data
        Session::forget('impersonating');

        // Switch back to admin user
        // For session-based auth (web guard)
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            Auth::guard('web')->login($admin);
        }

        // Regenerate session
        Session::regenerate();

        return true;
    }

    /**
     * Check if currently impersonating.
     */
    public function isImpersonating(): bool
    {
        return Session::has('impersonating');
    }

    /**
     * Get the impersonation data (decrypted).
     */
    public function getImpersonationData(): ?array
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        try {
            $encrypted = Session::get('impersonating');
            $decrypted = Crypt::decryptString($encrypted);

            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            // If decryption fails, clear the invalid session data
            Session::forget('impersonating');

            return null;
        }
    }

    /**
     * Get the admin user who is impersonating.
     */
    public function getImpersonatingAdmin(): ?Authenticatable
    {
        $data = $this->getImpersonationData();

        if (! $data) {
            return null;
        }

        $userModel = $this->getUserModel();

        return $userModel::find($data['admin_id']);
    }

    /**
     * Get impersonation status for API response.
     */
    public function getStatus(): array
    {
        if (! $this->isImpersonating()) {
            return [
                'is_impersonating' => false,
                'admin' => null,
                'started_at' => null,
            ];
        }

        $data = $this->getImpersonationData();
        $admin = $this->getImpersonatingAdmin();

        return [
            'is_impersonating' => true,
            'admin' => $admin ? [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ] : null,
            'started_at' => $data['started_at'] ?? null,
        ];
    }

    /**
     * Check if a user can initiate impersonation.
     */
    protected function canImpersonate(Authenticatable $user): bool
    {
        // Check for isAdmin method first
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // Check for super_admin role
        if (method_exists($user, 'hasRole')) {
            $superAdminRole = config('studio.authorization.super_admin_role', 'super_admin');

            return $user->hasRole($superAdminRole) || $user->hasRole('admin');
        }

        return false;
    }

    /**
     * Check if a user is an admin.
     */
    protected function isAdmin(Authenticatable $user): bool
    {
        // Check for isAdmin method
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        // Check for admin or super_admin roles
        if (method_exists($user, 'hasRole')) {
            $superAdminRole = config('studio.authorization.super_admin_role', 'super_admin');

            return $user->hasRole($superAdminRole) || $user->hasRole('admin');
        }

        return false;
    }
}
