<?php

namespace SavyApps\LaravelStudio\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SavyApps\LaravelStudio\Models\Setting;

class BulkUpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user is authenticated
        if (! $this->user()) {
            return false;
        }

        // Check each setting for permissions
        $settings = $this->input('settings', []);
        $isAdmin = $this->isUserAdmin();

        foreach ($settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                // Check if setting is public or if user is admin
                if (! $setting->is_public && ! $isAdmin) {
                    return false;
                }

                // Check scope-based permissions
                if ($setting->scope === 'admin' && ! $isAdmin) {
                    return false;
                }
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Settings data is required.',
            'settings.array' => 'Settings must be an array.',
            'settings.*.required' => 'Each setting must have a value.',
        ];
    }

    /**
     * Check if user is admin
     */
    protected function isUserAdmin(): bool
    {
        $user = $this->user();

        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        }

        return false;
    }
}
