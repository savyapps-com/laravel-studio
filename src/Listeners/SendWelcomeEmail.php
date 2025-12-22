<?php

namespace SavyApps\LaravelStudio\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use SavyApps\LaravelStudio\Notifications\TemplatedNotification;

class SendWelcomeEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        try {
            // Send welcome email using the 'user_welcome' template
            $user->notify(new TemplatedNotification('user_welcome', [
                'verification_url' => $this->getVerificationUrl($user),
            ]));

        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the email verification URL for the user.
     */
    protected function getVerificationUrl($user): ?string
    {
        if (!$user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) {
            return null;
        }

        if ($user->hasVerifiedEmail()) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }
}
