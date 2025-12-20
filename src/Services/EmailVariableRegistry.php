<?php

namespace SavyApps\LaravelStudio\Services;

class EmailVariableRegistry
{
    /**
     * Custom variable definitions registered by the application
     */
    protected array $customVariables = [];

    /**
     * Custom sample data providers registered by the application
     */
    protected array $customSampleDataProviders = [];

    /**
     * Register custom variables for a template key
     */
    public function registerVariables(string $key, array $variables): void
    {
        $this->customVariables[$key] = array_merge(
            $this->customVariables[$key] ?? [],
            $variables
        );
    }

    /**
     * Register a custom sample data provider for a template key
     */
    public function registerSampleDataProvider(string $key, callable $provider): void
    {
        $this->customSampleDataProviders[$key] = $provider;
    }

    public function getVariablesForTemplate(string $key): array
    {
        // Get default variables
        $defaultVariables = $this->getDefaultVariables($key);

        // Merge with custom variables
        return array_merge($defaultVariables, $this->customVariables[$key] ?? []);
    }

    public function getSampleData(string $key): array
    {
        // Check for custom provider first
        if (isset($this->customSampleDataProviders[$key])) {
            return call_user_func($this->customSampleDataProviders[$key]);
        }

        // Return default sample data
        return $this->getDefaultSampleData($key);
    }

    /**
     * Get default variable definitions for common templates
     */
    protected function getDefaultVariables(string $key): array
    {
        return match ($key) {
            'user_welcome' => [
                'user' => [
                    'label' => 'User Object',
                    'type' => 'object',
                    'description' => 'The registered user',
                    'properties' => [
                        'name' => 'string - User full name',
                        'email' => 'string - User email address',
                        'created_at' => 'Carbon - Registration date',
                    ],
                    'example' => '{{ $user->name }}, {{ $user->email }}',
                ],
                'verification_url' => [
                    'label' => 'Email Verification URL',
                    'type' => 'string',
                    'example' => '<a href="{{ $verification_url }}">Verify Email</a>',
                ],
            ],
            'password_reset' => [
                'user' => [
                    'label' => 'User Object',
                    'type' => 'object',
                    'properties' => [
                        'name' => 'string - User full name',
                        'email' => 'string - User email address',
                    ],
                    'example' => '{{ $user->name }}',
                ],
                'reset_url' => [
                    'label' => 'Password Reset URL',
                    'type' => 'string',
                    'example' => '<a href="{{ $reset_url }}">Reset Password</a>',
                ],
            ],
            'forgot_password' => [
                'user' => [
                    'label' => 'User Object',
                    'type' => 'object',
                    'properties' => [
                        'name' => 'string - User full name',
                    ],
                    'example' => '{{ $user->name }}',
                ],
                'reset_url' => [
                    'label' => 'Password Reset URL',
                    'type' => 'string',
                    'example' => '<a href="{{ $reset_url }}">Reset Password</a>',
                ],
                'expires_in' => [
                    'label' => 'Expiration Time',
                    'type' => 'string',
                    'example' => '{{ $expires_in }}',
                ],
            ],
            default => []
        };
    }

    /**
     * Get default sample data for common templates
     */
    protected function getDefaultSampleData(string $key): array
    {
        $userModel = config('studio.authorization.models.user', 'App\\Models\\User');

        return match ($key) {
            'user_welcome' => [
                'user' => (object) [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'created_at' => now(),
                ],
                'verification_url' => url('/verify-email/1/sample-hash'),
            ],
            'password_reset' => [
                'user' => (object) [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                ],
                'reset_url' => url('/reset-password/sample-token'),
            ],
            'forgot_password' => [
                'user' => (object) [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'reset_url' => url('/reset-password/abc123xyz'),
                'expires_in' => '60 minutes',
            ],
            default => []
        };
    }
}
