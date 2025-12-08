<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait for making Resources globally searchable.
 * Add this trait to your Resource class to enable global search.
 */
trait GloballySearchable
{
    /**
     * Determine if the resource is globally searchable.
     */
    public static function globallySearchable(): bool
    {
        return true;
    }

    /**
     * Get the columns that should be searched globally.
     * Override this method in your resource to customize.
     */
    public static function globallySearchableColumns(): array
    {
        // Default to the resource's searchable columns
        if (method_exists(static::class, 'searchableColumns')) {
            return static::searchableColumns();
        }

        return ['id'];
    }

    /**
     * Get the maximum number of results to return for this resource.
     */
    public static function globalSearchResultsLimit(): int
    {
        return 5;
    }

    /**
     * Get the search result representation for a model.
     */
    public static function globalSearchResult(Model $model): array
    {
        $titleColumn = static::globalSearchTitleColumn();
        $subtitleColumn = static::globalSearchSubtitleColumn();

        return [
            'id' => $model->getKey(),
            'title' => $model->{$titleColumn} ?? "#{$model->getKey()}",
            'subtitle' => $subtitleColumn ? ($model->{$subtitleColumn} ?? null) : null,
            'avatar' => static::globalSearchAvatar($model),
            'icon' => static::globalSearchIcon(),
            'meta' => static::globalSearchMeta($model),
            'url' => static::globalSearchUrl($model),
        ];
    }

    /**
     * Get the column to use as the title in search results.
     */
    public static function globalSearchTitleColumn(): string
    {
        return 'name';
    }

    /**
     * Get the column to use as the subtitle in search results.
     */
    public static function globalSearchSubtitleColumn(): ?string
    {
        return 'email';
    }

    /**
     * Get the avatar URL for search results.
     */
    public static function globalSearchAvatar(Model $model): ?string
    {
        // Try common avatar columns
        if (method_exists($model, 'getFirstMediaUrl')) {
            $url = $model->getFirstMediaUrl('avatar', 'thumb');
            if ($url) {
                return $url;
            }
        }

        if (isset($model->avatar_url)) {
            return $model->avatar_url;
        }

        if (isset($model->avatar)) {
            return $model->avatar;
        }

        return null;
    }

    /**
     * Get the icon for this resource type in search results.
     */
    public static function globalSearchIcon(): ?string
    {
        return null;
    }

    /**
     * Get additional meta information for search results.
     */
    public static function globalSearchMeta(Model $model): ?string
    {
        return null;
    }

    /**
     * Get the URL for the search result.
     */
    public static function globalSearchUrl(Model $model): ?string
    {
        return null;
    }

    /**
     * Perform the global search query.
     */
    public static function globalSearchQuery(string $search, int $limit = 5): array
    {
        $modelClass = static::model();
        $columns = static::globallySearchableColumns();

        if (empty($columns)) {
            return [];
        }

        $query = $modelClass::query();

        // Apply search conditions
        $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });

        // Apply any resource-specific scopes
        if (method_exists(static::class, 'globalSearchScope')) {
            static::globalSearchScope($query);
        }

        // Get results
        $results = $query->limit($limit)->get();

        // Transform results
        return $results->map(function ($model) {
            return static::globalSearchResult($model);
        })->toArray();
    }

    /**
     * Get the resource key for search grouping.
     */
    public static function globalSearchResourceKey(): string
    {
        if (method_exists(static::class, 'uriKey')) {
            return static::uriKey();
        }

        return strtolower(class_basename(static::class));
    }

    /**
     * Get the resource label for search grouping.
     */
    public static function globalSearchResourceLabel(): string
    {
        if (method_exists(static::class, 'label')) {
            return static::label();
        }

        return class_basename(static::class);
    }
}
