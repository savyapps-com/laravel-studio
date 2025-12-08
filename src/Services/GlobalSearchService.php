<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Facades\Cache;
use SavyApps\LaravelStudio\Traits\GloballySearchable;

class GlobalSearchService
{
    /**
     * Perform a global search across all searchable resources.
     */
    public function search(string $query, ?string $panel = null, ?array $resourceTypes = null): array
    {
        if (strlen($query) < $this->getMinCharacters()) {
            return [
                'query' => $query,
                'results' => [],
                'grouped' => [],
                'total' => 0,
            ];
        }

        $resources = $this->getSearchableResources($panel);
        $results = [];
        $grouped = [];

        foreach ($resources as $resourceKey => $resourceClass) {
            // Skip if specific resource types are requested and this isn't one
            if ($resourceTypes && !in_array($resourceKey, $resourceTypes)) {
                continue;
            }

            // Check if resource uses GloballySearchable trait
            if (!$this->isGloballySearchable($resourceClass)) {
                continue;
            }

            // Get results for this resource
            $limit = $resourceClass::globalSearchResultsLimit();
            $resourceResults = $resourceClass::globalSearchQuery($query, $limit);

            if (!empty($resourceResults)) {
                $grouped[$resourceKey] = [
                    'key' => $resourceKey,
                    'label' => $resourceClass::globalSearchResourceLabel(),
                    'icon' => $resourceClass::globalSearchIcon(),
                    'results' => $resourceResults,
                    'count' => count($resourceResults),
                ];

                foreach ($resourceResults as $result) {
                    $results[] = array_merge($result, [
                        'resource' => $resourceKey,
                        'resource_label' => $resourceClass::globalSearchResourceLabel(),
                    ]);
                }
            }
        }

        // Apply global result limit
        $maxResults = $this->getMaxResults();
        if (count($results) > $maxResults) {
            $results = array_slice($results, 0, $maxResults);
        }

        return [
            'query' => $query,
            'results' => $results,
            'grouped' => $grouped,
            'total' => count($results),
        ];
    }

    /**
     * Search within a specific resource type.
     */
    public function searchResource(string $resourceKey, string $query, int $limit = 10): array
    {
        $resourceClass = $this->getResourceClass($resourceKey);

        if (!$resourceClass || !$this->isGloballySearchable($resourceClass)) {
            return [];
        }

        return $resourceClass::globalSearchQuery($query, $limit);
    }

    /**
     * Get all searchable resources for a panel.
     */
    public function getSearchableResources(?string $panel = null): array
    {
        $allResources = config('studio.resources', []);
        $searchable = [];

        foreach ($allResources as $key => $resourceConfig) {
            $resourceClass = is_array($resourceConfig)
                ? ($resourceConfig['class'] ?? null)
                : $resourceConfig;

            if (!$resourceClass || !class_exists($resourceClass)) {
                continue;
            }

            // Check if globally searchable
            if (!$this->isGloballySearchable($resourceClass)) {
                continue;
            }

            // If panel specified, check if resource belongs to panel
            if ($panel) {
                $panelConfig = config("studio.panels.{$panel}", []);
                $panelResources = $panelConfig['resources'] ?? [];

                // If panel has specific resources, check if this one is included
                if (!empty($panelResources) && !in_array($key, $panelResources)) {
                    continue;
                }
            }

            $searchable[$key] = $resourceClass;
        }

        return $searchable;
    }

    /**
     * Get resource class by key.
     */
    protected function getResourceClass(string $resourceKey): ?string
    {
        $resources = config('studio.resources', []);

        if (!isset($resources[$resourceKey])) {
            return null;
        }

        $resourceConfig = $resources[$resourceKey];

        return is_array($resourceConfig)
            ? ($resourceConfig['class'] ?? null)
            : $resourceConfig;
    }

    /**
     * Check if a resource class is globally searchable.
     */
    protected function isGloballySearchable(string $resourceClass): bool
    {
        // Check if class uses the GloballySearchable trait
        $traits = class_uses_recursive($resourceClass);

        if (!in_array(GloballySearchable::class, $traits)) {
            return false;
        }

        // Check if search is enabled for this resource
        if (method_exists($resourceClass, 'globallySearchable')) {
            return $resourceClass::globallySearchable();
        }

        return true;
    }

    /**
     * Get quick search suggestions (recently searched or popular).
     */
    public function getSuggestions(?string $userId = null): array
    {
        $suggestions = [];

        // Get recent searches for user if available
        if ($userId) {
            $cacheKey = "global_search_recent_{$userId}";
            $recent = Cache::get($cacheKey, []);

            if (!empty($recent)) {
                $suggestions['recent'] = array_slice($recent, 0, 5);
            }
        }

        // Get searchable resource types
        $resources = $this->getSearchableResources();
        $suggestions['resources'] = [];

        foreach ($resources as $key => $resourceClass) {
            $suggestions['resources'][] = [
                'key' => $key,
                'label' => $resourceClass::globalSearchResourceLabel(),
                'icon' => $resourceClass::globalSearchIcon(),
            ];
        }

        return $suggestions;
    }

    /**
     * Store a recent search for the user.
     */
    public function storeRecentSearch(string $userId, string $query): void
    {
        $cacheKey = "global_search_recent_{$userId}";
        $recent = Cache::get($cacheKey, []);

        // Remove duplicate if exists
        $recent = array_filter($recent, fn($item) => $item !== $query);

        // Add to beginning
        array_unshift($recent, $query);

        // Keep only last 10
        $recent = array_slice($recent, 0, 10);

        // Cache for 30 days
        Cache::put($cacheKey, $recent, now()->addDays(30));
    }

    /**
     * Clear recent searches for a user.
     */
    public function clearRecentSearches(string $userId): void
    {
        Cache::forget("global_search_recent_{$userId}");
    }

    /**
     * Get minimum characters required for search.
     */
    protected function getMinCharacters(): int
    {
        return config('studio.global_search.min_characters', 2);
    }

    /**
     * Get maximum results to return.
     */
    protected function getMaxResults(): int
    {
        return config('studio.global_search.max_results', 20);
    }
}
