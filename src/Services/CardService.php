<?php

namespace SavyApps\LaravelStudio\Services;

use SavyApps\LaravelStudio\Cards\Card;

class CardService
{
    /**
     * Get cards for a resource.
     */
    public function getResourceCards(string $resourceKey): array
    {
        $resourceClass = $this->getResourceClass($resourceKey);

        if (!$resourceClass) {
            return [];
        }

        return $this->resolveCards($resourceClass);
    }

    /**
     * Get cards for a panel dashboard.
     */
    public function getDashboardCards(?string $panel = null): array
    {
        $widgetConfig = $panel
            ? config("studio.panels.{$panel}.widgets", [])
            : config('studio.dashboard.widgets', []);

        $cards = [];

        foreach ($widgetConfig as $config) {
            // If it's a card class reference
            if (isset($config['card'])) {
                $cardClass = $config['card'];
                if (class_exists($cardClass)) {
                    $card = app($cardClass);
                    if ($card instanceof Card && $card->isVisible()) {
                        $cards[] = $card->toArray();
                    }
                }
            }
            // If it's a widget component config
            elseif (isset($config['component'])) {
                $cards[] = [
                    'type' => 'widget',
                    'component' => $config['component'],
                    'width' => $config['width'] ?? '1/4',
                    'props' => $config['props'] ?? [],
                ];
            }
        }

        return $cards;
    }

    /**
     * Get a single card's data by key.
     */
    public function getCardData(string $resourceKey, string $cardKey): ?array
    {
        $cards = $this->getResourceCards($resourceKey);

        foreach ($cards as $card) {
            if ($card['key'] === $cardKey) {
                return $card;
            }
        }

        return null;
    }

    /**
     * Refresh a card's data (bypasses cache).
     */
    public function refreshCard(string $resourceKey, string $cardKey): ?array
    {
        $resourceClass = $this->getResourceClass($resourceKey);

        if (!$resourceClass) {
            return null;
        }

        $resource = app($resourceClass);

        if (!method_exists($resource, 'cards')) {
            return null;
        }

        $cards = $resource->cards();

        foreach ($cards as $card) {
            if ($card instanceof Card && $card->key() === $cardKey && $card->isVisible()) {
                // Clear cache for this card
                cache()->forget('studio_card_' . $cardKey);
                return $card->toArray();
            }
        }

        return null;
    }

    /**
     * Get all available card types.
     */
    public function getCardTypes(): array
    {
        return [
            'value' => [
                'name' => 'Value Card',
                'description' => 'Display a single metric with optional trend',
                'component' => 'value-card',
            ],
            'trend' => [
                'name' => 'Trend Card',
                'description' => 'Display a metric with comparison and optional chart',
                'component' => 'trend-card',
            ],
            'partition' => [
                'name' => 'Partition Card',
                'description' => 'Display data as pie, donut, or bar chart',
                'component' => 'partition-card',
            ],
            'table' => [
                'name' => 'Table Card',
                'description' => 'Display data in a mini table',
                'component' => 'table-card',
            ],
            'chart' => [
                'name' => 'Chart Card',
                'description' => 'Display line, bar, or area charts',
                'component' => 'chart-card',
            ],
        ];
    }

    /**
     * Clear all card caches for a resource.
     */
    public function clearResourceCardCache(string $resourceKey): void
    {
        $resourceClass = $this->getResourceClass($resourceKey);

        if (!$resourceClass) {
            return;
        }

        $resource = app($resourceClass);

        if (!method_exists($resource, 'cards')) {
            return;
        }

        $cards = $resource->cards();

        foreach ($cards as $card) {
            if ($card instanceof Card) {
                cache()->forget('studio_card_' . $card->key());
            }
        }
    }

    /**
     * Clear all card caches.
     */
    public function clearAllCardCaches(): void
    {
        $resources = config('studio.resources', []);

        foreach (array_keys($resources) as $key) {
            $this->clearResourceCardCache($key);
        }
    }

    /**
     * Resolve cards from a resource class.
     */
    protected function resolveCards(string $resourceClass): array
    {
        $resource = app($resourceClass);

        if (!method_exists($resource, 'cards')) {
            return [];
        }

        $cards = $resource->cards();
        $resolved = [];

        foreach ($cards as $card) {
            if ($card instanceof Card && $card->isVisible()) {
                $resolved[] = $card->toArray();
            }
        }

        return $resolved;
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
}
