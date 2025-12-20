<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use SavyApps\LaravelStudio\Models\Setting;

class SettingsService
{
    protected function getUserModel(): string
    {
        return config('studio.authorization.models.user', 'App\\Models\\User');
    }

    public function get(string $key, mixed $default = null, string $scope = 'global', ?int $settableId = null): mixed
    {
        $cacheKey = $this->getCacheKey($key, $scope, $settableId);

        if (config('studio.cache.enabled')) {
            return Cache::remember($cacheKey, config('studio.cache.ttl'), function () use ($key, $default, $scope, $settableId) {
                return $this->fetchSetting($key, $default, $scope, $settableId);
            });
        }

        return $this->fetchSetting($key, $default, $scope, $settableId);
    }

    public function set(string $key, mixed $value, string $scope = 'global', ?int $settableId = null): Setting
    {
        $settableType = $settableId ? $this->getUserModel() : null;

        // Try to get existing setting to preserve group, or use default from config
        $existingSetting = Setting::where('key', $key)
            ->where('scope', $scope)
            ->where('settable_type', $settableType)
            ->where('settable_id', $settableId)
            ->first();

        $group = $existingSetting?->group ?? $this->getGroupForKey($key) ?? 'general';
        $label = $existingSetting?->label ?? $this->getLabelForKey($key);
        $type = $existingSetting?->type ?? $this->inferType($value);

        $setting = Setting::updateOrCreate(
            [
                'key' => $key,
                'scope' => $scope,
                'settable_type' => $settableType,
                'settable_id' => $settableId,
            ],
            [
                'value' => $this->encodeValue($value),
                'type' => $type,
                'group' => $group,
                'label' => $label,
            ]
        );

        $this->clearCache($key, $scope, $settableId);

        return $setting->fresh();
    }

    /**
     * Get the group for a setting key from config or infer it.
     */
    protected function getGroupForKey(string $key): ?string
    {
        // Check if setting exists in config defaults
        $defaults = config('studio.settings.defaults', []);
        foreach ($defaults as $group => $settings) {
            if (isset($settings[$key])) {
                return $group;
            }
        }

        // Infer group from key prefix if possible
        if (str_starts_with($key, 'user_')) {
            return 'user';
        }
        if (in_array($key, ['items_per_page', 'date_format', 'time_format', 'language', 'timezone'])) {
            return 'preferences';
        }

        return null;
    }

    /**
     * Get a human-readable label for a setting key.
     */
    protected function getLabelForKey(string $key): string
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $key));
    }

    public function getByGroup(string $group, string $scope = 'global', ?int $settableId = null): array
    {
        $query = Setting::where('group', $group)->where('scope', $scope);

        if ($settableId) {
            $query->where('settable_id', $settableId)->where('settable_type', $this->getUserModel());
        } else {
            $query->whereNull('settable_type');
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getTypedValue()];
        })->toArray();
    }

    public function getForUser($user, ?string $group = null): array
    {
        $query = $user->settings();

        if ($group) {
            $query->where('group', $group);
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getTypedValue()];
        })->toArray();
    }

    public function setForUser($user, string $key, mixed $value): Setting
    {
        return $this->set($key, $value, 'user', $user->id);
    }

    public function getAllByScope(string $scope): array
    {
        $query = Setting::where('scope', $scope);

        if ($scope === 'global') {
            $query->whereNull('settable_type');
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getTypedValue()];
        })->toArray();
    }

    public function delete(string $key, string $scope = 'global', ?int $settableId = null): bool
    {
        $settableType = $settableId ? $this->getUserModel() : null;

        $deleted = Setting::where('key', $key)
            ->where('scope', $scope)
            ->where('settable_type', $settableType)
            ->where('settable_id', $settableId)
            ->delete();

        if ($deleted) {
            $this->clearCache($key, $scope, $settableId);
        }

        return $deleted > 0;
    }

    public function has(string $key, string $scope = 'global', ?int $settableId = null): bool
    {
        $settableType = $settableId ? $this->getUserModel() : null;

        return Setting::where('key', $key)
            ->where('scope', $scope)
            ->where('settable_type', $settableType)
            ->where('settable_id', $settableId)
            ->exists();
    }

    public function getWithReference(string $key, string $scope = 'global', ?int $settableId = null): ?Setting
    {
        $settableType = $settableId ? $this->getUserModel() : null;

        return Setting::with('referenceable')
            ->where('key', $key)
            ->where('scope', $scope)
            ->where('settable_type', $settableType)
            ->where('settable_id', $settableId)
            ->first();
    }

    public function validateSetting(string $key, mixed $value): array
    {
        $setting = Setting::where('key', $key)->first();

        if (! $setting || ! $setting->validation_rules) {
            return ['valid' => true, 'errors' => []];
        }

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $setting->validation_rules]
        );

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->get('value'),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    public function bulkSet(array $settings, string $scope = 'global', ?int $settableId = null): array
    {
        $results = [];

        foreach ($settings as $key => $value) {
            $results[$key] = $this->set($key, $value, $scope, $settableId);
        }

        return $results;
    }

    protected function fetchSetting(string $key, mixed $default, string $scope, ?int $settableId): mixed
    {
        $settableType = $settableId ? $this->getUserModel() : null;

        $setting = Setting::where('key', $key)
            ->where('scope', $scope)
            ->where('settable_type', $settableType)
            ->where('settable_id', $settableId)
            ->first();

        return $setting ? $setting->getTypedValue() : $default;
    }

    protected function getCacheKey(string $key, string $scope, ?int $settableId): string
    {
        $prefix = config('studio.cache.prefix', 'studio_');

        return "{$prefix}settings:{$scope}:{$key}:".($settableId ?? 'null');
    }

    protected function clearCache(string $key, string $scope, ?int $settableId): void
    {
        if (config('studio.cache.enabled')) {
            $cacheKey = $this->getCacheKey($key, $scope, $settableId);
            Cache::forget($cacheKey);
        }
    }

    /**
     * Encode value for storage (raw text, not JSON)
     */
    protected function encodeValue(mixed $value): string
    {
        // For arrays/objects, use JSON encoding
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        // For booleans, convert to string representation
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // For everything else (strings, numbers, null), convert to string
        return (string) $value;
    }

    /**
     * Infer the type of a value for storage in settings table.
     */
    protected function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'json';
        }

        // Default to string for everything else
        return 'string';
    }
}
