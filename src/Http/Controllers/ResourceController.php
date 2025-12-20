<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use SavyApps\LaravelStudio\Exceptions\StudioException;
use SavyApps\LaravelStudio\Http\Requests\ResourceStoreRequest;
use SavyApps\LaravelStudio\Http\Requests\ResourceUpdateRequest;
use SavyApps\LaravelStudio\Services\PanelService;
use SavyApps\LaravelStudio\Services\ResourceService;
use SavyApps\LaravelStudio\Traits\ApiResponse;

class ResourceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PanelService $panelService
    ) {}

    /**
     * Get resource metadata (fields, filters, actions).
     */
    public function meta(Request $request, string $resource): JsonResponse
    {
        try {
            $resourceInstance = $this->resolveResource($resource, $request);

            $context = $request->query('context', 'index');
            $fields = $context === 'form'
                ? $resourceInstance->getFormFields()
                : $resourceInstance->getIndexFields();

            return response()->json([
                'key' => $resourceInstance::key(),
                'model' => $resourceInstance::model(),
                'label' => $resourceInstance::$label,
                'singularLabel' => $resourceInstance::$singularLabel,
                'title' => $resourceInstance::$title,
                'searchable' => $resourceInstance::$searchable,
                'search' => $resourceInstance::$search,
                'perPage' => $resourceInstance::$perPage,
                'fields' => array_map(fn ($f) => $f->toArray(), $fields),
                'filters' => array_map(fn ($f) => $f->toArray(), $resourceInstance->filters()),
                'actions' => array_map(fn ($a) => $a->toArray(), $resourceInstance->actions()),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * List resources.
     */
    public function index(Request $request, string $resource): JsonResponse
    {
        try {
            $resourceInstance = $this->resolveResource($resource, $request);

            $this->authorizeResource($resourceInstance, 'viewAny');

            $service = $this->createResourceService($resourceInstance);
            $data = $service->index($request->all());

            return response()->json($data);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Show single resource.
     */
    public function show(Request $request, string $resource, int|string $id): JsonResponse
    {
        try {
            $resourceInstance = $this->resolveResource($resource, $request);
            $service = $this->createResourceService($resourceInstance);

            $model = $service->show($id);

            $this->authorizeResource($resourceInstance, 'view', $model);

            return response()->json([
                'data' => $model->toArray(),
            ]);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create resource.
     */
    public function store(Request $request, string $resource): JsonResponse
    {
        try {
            $resourceClass = $this->resolveResourceClass($resource, $request);
            $resourceInstance = $this->resolveResource($resource, $request);

            $this->authorizeResource($resourceInstance, 'create');

            $formRequest = app(ResourceStoreRequest::class);
            $formRequest->setResource($resourceClass);

            $validated = $this->validateRequest(
                $request->all(),
                $formRequest->rules(),
                $resourceInstance->messages()
            );

            $service = $this->createResourceService($resourceInstance);
            $model = $service->store($validated);

            return response()->json([
                'message' => $resourceInstance::$singularLabel . ' created successfully',
                'data' => $resourceInstance->transform($model),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update resource.
     */
    public function update(Request $request, string $resource, int|string $id): JsonResponse
    {
        try {
            $resourceClass = $this->resolveResourceClass($resource, $request);
            $resourceInstance = $this->resolveResource($resource, $request);

            $service = $this->createResourceService($resourceInstance);
            $model = $service->show($id);

            $this->authorizeResource($resourceInstance, 'update', $model);

            $formRequest = app(ResourceUpdateRequest::class);
            $formRequest->setResource($resourceClass);
            $formRequest->setRouteResolver(fn () => $request->route());

            $validated = $this->validateRequest(
                $request->all(),
                $formRequest->rules(),
                $resourceInstance->messages()
            );

            $model = $service->update($id, $validated);

            return response()->json([
                'message' => $resourceInstance::$singularLabel . ' updated successfully',
                'data' => $resourceInstance->transform($model),
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Partially update resource (for toggles and quick edits).
     */
    public function patch(Request $request, string $resource, int|string $id): JsonResponse
    {
        try {
            $resourceInstance = $this->resolveResource($resource, $request);

            $service = $this->createResourceService($resourceInstance);
            $model = $service->show($id);

            $this->authorizeResource($resourceInstance, 'update', $model);

            $fields = $this->filterVisibleFieldsDataForPatch($resourceInstance, $request->all());

            if (empty($fields)) {
                return $this->errorResponse('No fields to update', 422);
            }

            $rules = $this->buildPatchValidationRules($resourceInstance, $fields, $id);

            $validated = $this->validateRequest(
                $fields,
                $rules,
                $resourceInstance->messages()
            );

            $model = $service->update($id, $validated);

            return response()->json([
                'message' => $resourceInstance::$singularLabel . ' updated successfully',
                'data' => $resourceInstance->transform($model),
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete resource.
     */
    public function destroy(Request $request, string $resource, int|string $id): JsonResponse
    {
        try {
            $resourceInstance = $this->resolveResource($resource, $request);
            $service = $this->createResourceService($resourceInstance);

            $model = $service->show($id);

            $this->authorizeResource($resourceInstance, 'delete', $model);

            $service->destroy($id);

            return response()->json([
                'message' => $resourceInstance::$singularLabel . ' deleted successfully',
            ]);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk delete.
     */
    public function bulkDelete(Request $request, string $resource): JsonResponse
    {
        try {
            $maxBulkIds = config('studio.bulk_operations.max_ids', 1000);

            $request->validate([
                'ids' => "required|array|max:{$maxBulkIds}",
                'ids.*' => 'required|integer',
            ]);

            $resourceInstance = $this->resolveResource($resource, $request);

            $this->authorizeResource($resourceInstance, 'bulkDelete');

            $service = $this->createResourceService($resourceInstance);
            $count = $service->bulkDestroy($request->input('ids'));

            return response()->json([
                'message' => "{$count} items deleted successfully",
                'affected' => $count,
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update.
     */
    public function bulkUpdate(Request $request, string $resource): JsonResponse
    {
        try {
            $maxBulkIds = config('studio.bulk_operations.max_ids', 1000);

            $request->validate([
                'ids' => "required|array|max:{$maxBulkIds}",
                'ids.*' => 'required|integer',
                'data' => 'required|array',
            ]);

            $resourceInstance = $this->resolveResource($resource, $request);

            $this->authorizeResource($resourceInstance, 'bulkUpdate');

            $service = $this->createResourceService($resourceInstance);
            $count = $service->bulkUpdate(
                $request->input('ids'),
                $request->input('data')
            );

            return response()->json([
                'message' => "{$count} items updated successfully",
                'affected' => $count,
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Run action.
     */
    public function runAction(Request $request, string $resource, string $action): JsonResponse
    {
        try {
            $maxBulkIds = config('studio.bulk_operations.max_ids', 1000);

            $request->validate([
                'ids' => "required|array|max:{$maxBulkIds}",
                'ids.*' => 'required|integer',
            ]);

            $resourceInstance = $this->resolveResource($resource, $request);

            $this->authorizeResource($resourceInstance, 'runAction', $action);

            $service = $this->createResourceService($resourceInstance);
            $result = $service->runAction(
                $action,
                $request->input('ids'),
                $request->input('data', [])
            );

            return response()->json([
                'message' => 'Action completed successfully',
                'result' => $result,
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (StudioException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Search related resources (for BelongsTo fields).
     */
    public function searchRelated(Request $request, string $resource): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'nullable|string|max:255',
            ]);

            $resourceInstance = $this->resolveResource($resource, $request);
            $service = $this->createResourceService($resourceInstance);

            $data = $service->index([
                'search' => $request->input('query'),
                'perPage' => 20,
            ]);

            return response()->json($data);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create a ResourceService instance for the given resource.
     */
    protected function createResourceService(object $resourceInstance): ResourceService
    {
        return new ResourceService($resourceInstance);
    }

    /**
     * Validate request data and return validated array.
     *
     * @throws ValidationException
     */
    protected function validateRequest(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Build validation rules for PATCH requests.
     * Handles unique rule modifications and removes required constraints.
     */
    protected function buildPatchValidationRules(object $resourceInstance, array $fields, int|string $id): array
    {
        $allRules = $resourceInstance->rules('update');
        $rules = [];

        foreach ($fields as $field => $_value) {
            if (!isset($allRules[$field])) {
                continue;
            }

            $rule = $allRules[$field];

            // Handle array of rules
            if (is_array($rule)) {
                $rule = $this->processArrayRules($rule, $field, $id);
            } elseif (is_string($rule)) {
                $rule = $this->processStringRule($rule, $field, $id);
            }

            if (!empty($rule)) {
                $rules[$field] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Process array-based validation rules for PATCH.
     */
    protected function processArrayRules(array $rules, string $field, int|string $id): array
    {
        $processed = [];

        foreach ($rules as $rule) {
            // Skip 'required' for partial updates
            if ($rule === 'required') {
                continue;
            }

            // Handle Rule objects (like Rule::unique())
            if ($rule instanceof Rule) {
                $processed[] = $rule;
                continue;
            }

            // Handle string rules
            if (is_string($rule)) {
                if (str_contains($rule, 'unique:')) {
                    $rule = $this->modifyUniqueRule($rule, $field, $id);
                }
                $processed[] = $rule;
            } else {
                $processed[] = $rule;
            }
        }

        return $processed;
    }

    /**
     * Process string-based validation rule for PATCH.
     */
    protected function processStringRule(string $rule, string $field, int|string $id): string
    {
        // Handle unique rules
        if (str_contains($rule, 'unique:')) {
            $rule = $this->modifyUniqueRule($rule, $field, $id);
        }

        // Remove 'required' constraint for partial updates
        $rule = preg_replace('/\brequired\|?/', '', $rule);
        $rule = preg_replace('/\|?required\b/', '', $rule);
        $rule = trim($rule, '|');

        return $rule;
    }

    /**
     * Modify unique validation rule to ignore current record.
     */
    protected function modifyUniqueRule(string $rule, string $field, int|string $id): string
    {
        // Match patterns like: unique:table,column or unique:table
        if (preg_match('/unique:([^,|]+),([^,|]+)/', $rule, $matches)) {
            // Has table and column specified
            return preg_replace(
                '/unique:([^,|]+),([^,|]+)/',
                "unique:$1,$2,{$id}",
                $rule
            );
        }

        if (preg_match('/unique:([^,|]+)/', $rule, $matches)) {
            // Only table specified, add field and id
            return preg_replace(
                '/unique:([^,|]+)/',
                "unique:$1,{$field},{$id}",
                $rule
            );
        }

        return $rule;
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Resolve resource class from key.
     */
    protected function resolveResourceClass(string $resourceKey, ?Request $request = null): string
    {
        if ($request) {
            $panel = $request->get('_panel') ?? $request->attributes->get('panel');

            if ($panel && !$this->panelService->panelHasResource($panel, $resourceKey)) {
                abort(403, "Resource '{$resourceKey}' is not available in panel '{$panel}'");
            }
        }

        $resourceConfig = config("studio.resources.{$resourceKey}");

        $resourceClass = is_array($resourceConfig)
            ? ($resourceConfig['class'] ?? null)
            : $resourceConfig;

        if (!$resourceClass || !class_exists($resourceClass)) {
            abort(404, "Resource not found: {$resourceKey}");
        }

        return $resourceClass;
    }

    /**
     * Resolve resource instance from key.
     */
    protected function resolveResource(string $resourceKey, ?Request $request = null): object
    {
        $resourceClass = $this->resolveResourceClass($resourceKey, $request);

        return new $resourceClass;
    }

    /**
     * Filter patch data to only visible fields.
     */
    protected function filterVisibleFieldsDataForPatch(object $resourceInstance, array $data): array
    {
        $visibleFields = $resourceInstance->getVisibleFields($data);

        $allowedAttributes = [];
        foreach ($visibleFields as $field) {
            $allowedAttributes[] = $field->attribute;
        }

        return array_intersect_key($data, array_flip($allowedAttributes));
    }

    /**
     * Authorize resource action.
     */
    protected function authorizeResource(object $resourceInstance, string $ability, $model = null): void
    {
        if (method_exists($resourceInstance, 'authorize')) {
            $resourceInstance::authorize($ability, $model);
        }
    }
}
