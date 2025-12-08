<?php

namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SavyApps\LaravelStudio\Models\Activity;
use SavyApps\LaravelStudio\Services\ActivityService;

class ActivityController extends Controller
{
    public function __construct(
        protected ActivityService $activityService
    ) {}

    /**
     * Get paginated list of activities.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'log_name',
            'event',
            'subject_type',
            'causer_id',
            'causer_type',
            'from_date',
            'to_date',
            'batch_uuid',
            'search',
            'per_page',
        ]);

        $activities = $this->activityService->getActivities($filters);

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get a single activity.
     */
    public function show(int $id): JsonResponse
    {
        $activity = Activity::with(['subject', 'causer'])->findOrFail($id);

        return response()->json([
            'data' => $activity,
        ]);
    }

    /**
     * Get activities for a specific subject.
     */
    public function forSubject(Request $request, string $subjectType, int $subjectId): JsonResponse
    {
        // Resolve the subject model
        $modelClass = $this->resolveModelClass($subjectType);
        $subject = $modelClass::findOrFail($subjectId);

        $filters = $request->only([
            'event',
            'from_date',
            'to_date',
            'per_page',
        ]);

        $activities = $this->activityService->getSubjectActivities($subject, $filters);

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activities for the current user.
     */
    public function myActivities(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $filters = $request->only([
            'log_name',
            'event',
            'from_date',
            'to_date',
            'per_page',
        ]);

        $activities = $this->activityService->getUserActivities($user, $filters);

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activity statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only([
            'log_name',
            'event',
            'subject_type',
            'causer_id',
            'from_date',
            'to_date',
        ]);

        $stats = $this->activityService->getStatistics($filters);

        return response()->json($stats);
    }

    /**
     * Get recent activities.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $logName = $request->input('log_name');

        $activities = $this->activityService->getRecentActivities($limit, $logName);

        return response()->json([
            'data' => $activities,
        ]);
    }

    /**
     * Get available filter options.
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json([
            'log_names' => $this->activityService->getLogNames(),
            'event_types' => $this->activityService->getEventTypes(),
            'subject_types' => $this->activityService->getSubjectTypes(),
        ]);
    }

    /**
     * Delete a single activity (admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully',
        ]);
    }

    /**
     * Bulk delete activities (admin only).
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        $count = Activity::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => "{$count} activities deleted successfully",
            'affected' => $count,
        ]);
    }

    /**
     * Cleanup old activities (admin only).
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1',
        ]);

        $days = $request->input('days');
        $count = $this->activityService->cleanup($days);

        return response()->json([
            'message' => "{$count} old activities cleaned up",
            'affected' => $count,
        ]);
    }

    /**
     * Resolve model class from subject type.
     */
    protected function resolveModelClass(string $subjectType): string
    {
        // Try to find in registered resources first
        $resources = config('studio.resources', []);

        foreach ($resources as $key => $resourceConfig) {
            $resourceClass = is_array($resourceConfig)
                ? ($resourceConfig['class'] ?? null)
                : $resourceConfig;

            if (!$resourceClass || !class_exists($resourceClass)) {
                continue;
            }

            if (method_exists($resourceClass, 'model')) {
                $modelClass = $resourceClass::model();
                if (class_basename($modelClass) === $subjectType || $modelClass === $subjectType) {
                    return $modelClass;
                }
            }
        }

        // Fallback: try common namespaces
        $namespaces = [
            'App\\Models\\',
            'App\\',
        ];

        foreach ($namespaces as $namespace) {
            $class = $namespace . $subjectType;
            if (class_exists($class)) {
                return $class;
            }
        }

        // If it's already a fully qualified class name
        if (class_exists($subjectType)) {
            return $subjectType;
        }

        abort(404, "Subject type not found: {$subjectType}");
    }
}
