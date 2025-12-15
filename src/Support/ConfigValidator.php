<?php

namespace SavyApps\LaravelStudio\Support;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ConfigValidator
{
    /**
     * Validation errors collected during validation.
     */
    protected array $errors = [];

    /**
     * Validate the studio configuration.
     *
     * @throws InvalidArgumentException If critical configuration is invalid
     */
    public function validate(): bool
    {
        $this->errors = [];

        $this->validateCache();
        $this->validateBulkOperations();
        $this->validateAuthorization();
        $this->validateActivityLog();
        $this->validateGlobalSearch();
        $this->validateCards();
        $this->validateResources();

        if (!empty($this->errors)) {
            $this->logErrors();

            if ($this->hasCriticalErrors()) {
                throw new InvalidArgumentException(
                    'Laravel Studio configuration is invalid: ' . implode('; ', $this->getCriticalErrors())
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Validate unified cache configuration.
     */
    protected function validateCache(): void
    {
        $config = config('studio.cache', []);

        // Validate cache TTL
        $ttl = $config['ttl'] ?? null;
        if ($ttl !== null && (!is_int($ttl) || $ttl < 0)) {
            $this->errors[] = [
                'key' => 'cache.ttl',
                'message' => 'cache.ttl must be a non-negative integer',
                'critical' => false,
            ];
        }

        // Validate cache prefix
        $prefix = $config['prefix'] ?? null;
        if ($prefix !== null && !is_string($prefix)) {
            $this->errors[] = [
                'key' => 'cache.prefix',
                'message' => 'cache.prefix must be a string',
                'critical' => false,
            ];
        }
    }

    /**
     * Get all validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate bulk operations configuration.
     */
    protected function validateBulkOperations(): void
    {
        $maxIds = config('studio.bulk_operations.max_ids');
        $chunkSize = config('studio.bulk_operations.chunk_size');

        if ($maxIds !== null && (!is_int($maxIds) || $maxIds < 1)) {
            $this->errors[] = [
                'key' => 'bulk_operations.max_ids',
                'message' => 'bulk_operations.max_ids must be a positive integer',
                'critical' => false,
            ];
        }

        if ($chunkSize !== null && (!is_int($chunkSize) || $chunkSize < 1)) {
            $this->errors[] = [
                'key' => 'bulk_operations.chunk_size',
                'message' => 'bulk_operations.chunk_size must be a positive integer',
                'critical' => false,
            ];
        }

        if ($maxIds && $chunkSize && $chunkSize > $maxIds) {
            $this->errors[] = [
                'key' => 'bulk_operations.chunk_size',
                'message' => 'bulk_operations.chunk_size should not exceed max_ids',
                'critical' => false,
            ];
        }
    }

    /**
     * Validate authorization configuration.
     */
    protected function validateAuthorization(): void
    {
        $config = config('studio.authorization', []);

        // Validate super admin role is not empty when auth is enabled
        if (($config['enabled'] ?? true) && empty($config['super_admin_role'])) {
            $this->errors[] = [
                'key' => 'authorization.super_admin_role',
                'message' => 'authorization.super_admin_role must be set when authorization is enabled',
                'critical' => false,
            ];
        }

        // Validate model classes exist
        $models = $config['models'] ?? [];
        foreach (['user', 'role', 'permission'] as $modelType) {
            $modelClass = $models[$modelType] ?? null;
            if ($modelClass && !class_exists($modelClass)) {
                $this->errors[] = [
                    'key' => "authorization.models.{$modelType}",
                    'message' => "Model class '{$modelClass}' does not exist",
                    'critical' => false,
                ];
            }
        }
    }

    /**
     * Validate activity log configuration.
     */
    protected function validateActivityLog(): void
    {
        $config = config('studio.activity_log', []);

        // Validate cleanup_days
        $cleanupDays = $config['cleanup_days'] ?? null;
        if ($cleanupDays !== null && (!is_int($cleanupDays) || $cleanupDays < 0)) {
            $this->errors[] = [
                'key' => 'activity_log.cleanup_days',
                'message' => 'activity_log.cleanup_days must be a non-negative integer',
                'critical' => false,
            ];
        }

        // Validate per_page
        $perPage = $config['per_page'] ?? null;
        if ($perPage !== null && (!is_int($perPage) || $perPage < 1 || $perPage > 100)) {
            $this->errors[] = [
                'key' => 'activity_log.per_page',
                'message' => 'activity_log.per_page must be between 1 and 100',
                'critical' => false,
            ];
        }

        // Validate model class
        $modelClass = $config['model'] ?? null;
        if ($modelClass && !class_exists($modelClass)) {
            $this->errors[] = [
                'key' => 'activity_log.model',
                'message' => "Activity model class '{$modelClass}' does not exist",
                'critical' => false,
            ];
        }
    }

    /**
     * Validate global search configuration.
     */
    protected function validateGlobalSearch(): void
    {
        $config = config('studio.global_search', []);

        // Validate min_characters
        $minChars = $config['min_characters'] ?? null;
        if ($minChars !== null && (!is_int($minChars) || $minChars < 1)) {
            $this->errors[] = [
                'key' => 'global_search.min_characters',
                'message' => 'global_search.min_characters must be a positive integer',
                'critical' => false,
            ];
        }

        // Validate max_results
        $maxResults = $config['max_results'] ?? null;
        if ($maxResults !== null && (!is_int($maxResults) || $maxResults < 1)) {
            $this->errors[] = [
                'key' => 'global_search.max_results',
                'message' => 'global_search.max_results must be a positive integer',
                'critical' => false,
            ];
        }
    }

    /**
     * Validate cards configuration.
     */
    protected function validateCards(): void
    {
        $config = config('studio.cards', []);

        // Validate max_per_row
        $maxPerRow = $config['max_per_row'] ?? null;
        if ($maxPerRow !== null && (!is_int($maxPerRow) || $maxPerRow < 1 || $maxPerRow > 12)) {
            $this->errors[] = [
                'key' => 'cards.max_per_row',
                'message' => 'cards.max_per_row must be between 1 and 12',
                'critical' => false,
            ];
        }
    }

    /**
     * Validate resources configuration.
     */
    protected function validateResources(): void
    {
        $resources = config('studio.resources', []);

        foreach ($resources as $key => $config) {
            // Validate resource key format
            if (!preg_match('/^[a-z0-9\-_]+$/', $key)) {
                $this->errors[] = [
                    'key' => "resources.{$key}",
                    'message' => "Resource key '{$key}' must contain only lowercase letters, numbers, hyphens, and underscores",
                    'critical' => false,
                ];
            }

            // Get class from config (supports both formats)
            $resourceClass = is_array($config) ? ($config['class'] ?? null) : $config;

            // Validate resource class exists
            if (!$resourceClass) {
                $this->errors[] = [
                    'key' => "resources.{$key}",
                    'message' => "Resource '{$key}' must have a class defined",
                    'critical' => true,
                ];
            } elseif (!class_exists($resourceClass)) {
                $this->errors[] = [
                    'key' => "resources.{$key}",
                    'message' => "Resource class '{$resourceClass}' does not exist",
                    'critical' => true,
                ];
            }
        }
    }

    /**
     * Check if there are any critical errors.
     */
    protected function hasCriticalErrors(): bool
    {
        foreach ($this->errors as $error) {
            if ($error['critical'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get critical errors only.
     */
    protected function getCriticalErrors(): array
    {
        return array_map(
            fn ($error) => $error['message'],
            array_filter($this->errors, fn ($error) => $error['critical'] ?? false)
        );
    }

    /**
     * Log all validation errors.
     */
    protected function logErrors(): void
    {
        foreach ($this->errors as $error) {
            $level = ($error['critical'] ?? false) ? 'error' : 'warning';
            Log::$level("Laravel Studio config issue [{$error['key']}]: {$error['message']}");
        }
    }
}
