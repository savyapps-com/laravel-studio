<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use SavyApps\LaravelStudio\Resources\Resource;

class ResourceService
{
    public function __construct(protected Resource $resource) {}

    /**
     * Get paginated index of resources.
     */
    public function index(array $params = []): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        // Apply search
        if (! empty($params['search']) && $this->resource::$searchable) {
            $this->applySearch($query, $params['search']);
        }

        // Apply filters
        if (! empty($params['filters']) && is_array($params['filters'])) {
            $this->applyFilters($query, $params['filters']);
        }

        // Apply sorting
        if (! empty($params['sort'])) {
            $this->applySort($query, $params['sort'], $params['direction'] ?? 'asc');
        } else {
            // Default sorting by ID descending
            $query->orderBy('id', 'desc');
        }

        // Eager load relationships
        $with = $this->getRelationshipsToLoad();
        if (! empty($with)) {
            $query->with($with);
        }

        $perPage = $params['perPage'] ?? $this->resource::$perPage;

        $paginator = $query->paginate($perPage);

        // Transform each item through resource fields
        $paginator->getCollection()->transform(function ($item) {
            return $this->transformModel($item);
        });

        return $paginator;
    }

    /**
     * Transform a model through resource fields.
     */
    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        $fields = $this->resource->flattenFields($this->resource->getIndexFields());

        foreach ($fields as $field) {
            if (method_exists($field, 'transformValue')) {
                $data[$field->attribute] = $field->transformValue(
                    $model->getAttribute($field->attribute),
                    $model
                );
            }
        }

        return $data;
    }

    /**
     * Get a single resource.
     */
    public function show(int|string $id): Model
    {
        $query = $this->baseQuery();

        $with = $this->getRelationshipsToLoad();
        if (! empty($with)) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new resource.
     */
    public function store(array $data): Model
    {
        // Filter data to only include visible fields
        $data = $this->filterVisibleFieldsData($data);

        $modelClass = $this->resource::model();

        // Separate relationship data
        $relationshipData = $this->extractRelationshipData($data);
        $modelData = array_diff_key($data, $relationshipData);

        // Hash password if present
        if (isset($modelData['password'])) {
            $modelData['password'] = bcrypt($modelData['password']);
        }

        $model = $modelClass::create($modelData);

        // Handle relationships
        $this->syncRelationships($model, $relationshipData);

        return $model->fresh($this->getRelationshipsToLoad());
    }

    /**
     * Update a resource.
     */
    public function update(int|string $id, array $data): Model
    {
        // Filter data to only include visible fields
        $data = $this->filterVisibleFieldsData($data);

        $model = $this->baseQuery()->findOrFail($id);

        // Separate relationship data
        $relationshipData = $this->extractRelationshipData($data);
        $modelData = array_diff_key($data, $relationshipData);

        // Hash password if present and not empty
        if (isset($modelData['password']) && ! empty($modelData['password'])) {
            $modelData['password'] = bcrypt($modelData['password']);
        } else {
            // Remove password from update if empty
            unset($modelData['password']);
        }

        $model->update($modelData);

        // Handle relationships
        $this->syncRelationships($model, $relationshipData);

        return $model->fresh($this->getRelationshipsToLoad());
    }

    /**
     * Delete a resource.
     */
    public function destroy(int|string $id): bool
    {
        $model = $this->baseQuery()->findOrFail($id);

        return $model->delete();
    }

    /**
     * Bulk delete resources.
     */
    public function bulkDestroy(array $ids): int
    {
        return $this->baseQuery()->whereIn('id', $ids)->delete();
    }

    /**
     * Bulk update resources.
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        return $this->baseQuery()->whereIn('id', $ids)->update($data);
    }

    /**
     * Run an action on resources.
     */
    public function runAction(string $actionKey, array $ids, array $data = []): mixed
    {
        $action = collect($this->resource->actions())
            ->firstWhere('key', $actionKey);

        if (! $action) {
            throw new \InvalidArgumentException("Action not found: {$actionKey}");
        }

        // Eager load relationships to prevent N+1 queries in action handlers
        $query = $this->baseQuery()->whereIn('id', $ids);

        $with = $this->getRelationshipsToLoad();
        if (!empty($with)) {
            $query->with($with);
        }

        $models = $query->get();

        return $action->handle($models, $data);
    }

    /**
     * Get base query for the resource.
     */
    protected function baseQuery(): Builder
    {
        $modelClass = $this->resource::model();

        return $modelClass::query();
    }

    /**
     * Apply search to query.
     */
    protected function applySearch(Builder $query, string $search): void
    {
        if (empty($this->resource::$search)) {
            return;
        }

        $query->where(function ($q) use ($search) {
            foreach ($this->resource::$search as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Find the filter definition
            foreach ($this->resource->filters() as $filter) {
                if ($filter->key === $key) {
                    $filter->apply($query, $value);
                    break;
                }
            }
        }
    }

    /**
     * Apply sorting to query.
     * Only allows sorting on columns marked as sortable in the resource fields.
     */
    protected function applySort(Builder $query, string $column, string $direction = 'asc'): void
    {
        // Validate column against allowed sortable fields to prevent SQL injection
        $allowedColumns = $this->getSortableColumns();

        if (!in_array($column, $allowedColumns, true)) {
            // Fall back to default sorting if column is not allowed
            $query->orderBy('id', 'desc');
            return;
        }

        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($column, $direction);
    }

    /**
     * Get list of sortable column names from resource fields.
     */
    protected function getSortableColumns(): array
    {
        $fields = $this->resource->flattenFields($this->resource->getIndexFields());
        $sortableColumns = ['id', 'created_at', 'updated_at']; // Always allow these

        foreach ($fields as $field) {
            if ($field->sortable) {
                $sortableColumns[] = $field->attribute;
            }
        }

        return array_unique($sortableColumns);
    }

    /**
     * Get relationships to eager load.
     */
    protected function getRelationshipsToLoad(): array
    {
        $relationships = $this->resource->with();

        // Add BelongsTo relationships from index fields
        $fields = $this->resource->flattenFields($this->resource->getIndexFields());

        foreach ($fields as $field) {
            if ($field instanceof \SavyApps\LaravelStudio\Resources\Fields\BelongsTo) {
                $relationName = $field->relationName();
                // Only add if the relation exists on the model
                $modelClass = $this->resource::model();
                if (method_exists($modelClass, $relationName) && ! in_array($relationName, $relationships)) {
                    $relationships[] = $relationName;
                }
            }
        }

        return $relationships;
    }

    /**
     * Extract relationship data from request data.
     */
    protected function extractRelationshipData(array $data): array
    {
        $relationshipData = [];

        $fields = $this->resource->flattenFields($this->resource->getFormFields());

        foreach ($fields as $field) {
            if ($field instanceof \SavyApps\LaravelStudio\Resources\Fields\BelongsToMany) {
                if (isset($data[$field->attribute])) {
                    $relationshipData[$field->attribute] = $data[$field->attribute];
                }
            } elseif ($field instanceof \SavyApps\LaravelStudio\Resources\Fields\Select) {
                // Handle Select fields with multiple and resource (relationship fields)
                $meta = $field->meta();
                if (isset($meta['multiple']) && $meta['multiple'] && isset($meta['resource'])) {
                    if (isset($data[$field->attribute])) {
                        $relationshipData[$field->attribute] = $data[$field->attribute];
                    }
                }
            }
        }

        return $relationshipData;
    }

    /**
     * Sync relationships for a model.
     */
    protected function syncRelationships(Model $model, array $relationshipData): void
    {
        $fields = $this->resource->flattenFields($this->resource->getFormFields());

        foreach ($fields as $field) {
            if ($field instanceof \SavyApps\LaravelStudio\Resources\Fields\BelongsToMany) {
                $relationName = $field->attribute;
                if (isset($relationshipData[$relationName])) {
                    $this->syncBelongsToManyRelationship($model, $relationName, $relationshipData[$relationName], $field);
                }
            } elseif ($field instanceof \SavyApps\LaravelStudio\Resources\Fields\Select) {
                // Handle Select fields with multiple and resource (relationship fields)
                $meta = $field->meta();
                if (isset($meta['multiple']) && $meta['multiple'] && isset($meta['resource'])) {
                    $relationName = $field->attribute;
                    if (isset($relationshipData[$relationName])) {
                        $this->syncBelongsToManyRelationship($model, $relationName, $relationshipData[$relationName], $field);
                    }
                }
            }
        }
    }

    /**
     * Sync a BelongsToMany relationship, handling unique constraints intelligently.
     *
     * If the field is marked with enforceUniqueRelated(), this method will detach
     * the related records from all other models before syncing.
     *
     * Example: role_user relationship where each user can only have one role
     */
    protected function syncBelongsToManyRelationship(Model $model, string $relationName, array $relatedIds, $field = null): void
    {
        $relation = $model->$relationName();

        // Check if we should enforce uniqueness by detaching from other models first
        // This is controlled by the enforceUniqueRelated() method on the field
        if ($field && method_exists($field, 'shouldEnforceUniqueRelated') && $field->shouldEnforceUniqueRelated()) {
            // Get pivot table information
            $pivotTable = $relation->getTable();
            $foreignPivotKey = $relation->getForeignPivotKeyName(); // e.g., 'role_id'
            $relatedPivotKey = $relation->getRelatedPivotKeyName(); // e.g., 'user_id'

            // Detach these related IDs from ALL other models (except current model)
            // This ensures each related record can only be associated with one parent
            \DB::table($pivotTable)
                ->whereIn($relatedPivotKey, $relatedIds)
                ->where($foreignPivotKey, '!=', $model->getKey())
                ->delete();
        }

        // Now perform the normal sync
        $relation->sync($relatedIds);
    }

    /**
     * Filter data to only include visible fields.
     */
    protected function filterVisibleFieldsData(array $data): array
    {
        // Get visible fields based on form data
        $visibleFields = $this->resource->getVisibleFields($data);

        // Extract allowed attributes from visible fields
        $allowedAttributes = [];
        foreach ($visibleFields as $field) {
            $allowedAttributes[] = $field->attribute;
        }

        // Filter data to only include allowed attributes
        return array_intersect_key($data, array_flip($allowedAttributes));
    }
}
