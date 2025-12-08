<?php

namespace SavyApps\LaravelStudio\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SavyApps\LaravelStudio\Models\Activity;

class ActivityService
{
    /**
     * Log an activity.
     */
    public function log(
        string $event,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        ?string $logName = 'default',
        ?string $description = null
    ): Activity {
        $builder = Activity::log($event)->inLog($logName);

        if ($subject) {
            $builder->performedOn($subject);
        }

        if ($causer) {
            $builder->causedBy($causer);
        } else {
            $builder->causedBy(Auth::user());
        }

        if (!empty($properties)) {
            $builder->withProperties($properties);
        }

        if ($description) {
            $builder->withDescription($description);
        }

        return $builder->save();
    }

    /**
     * Get activities with filtering and pagination.
     */
    public function getActivities(array $filters = []): LengthAwarePaginator
    {
        $query = Activity::query()->with(['subject', 'causer'])->latest();

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? config('studio.activity_log.per_page', 25);

        return $query->paginate($perPage);
    }

    /**
     * Get activities for a specific subject.
     */
    public function getSubjectActivities(Model $subject, array $filters = []): LengthAwarePaginator
    {
        $query = Activity::query()
            ->forSubject($subject)
            ->with('causer')
            ->latest();

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? config('studio.activity_log.per_page', 25);

        return $query->paginate($perPage);
    }

    /**
     * Get activities caused by a specific user.
     */
    public function getUserActivities(Model $user, array $filters = []): LengthAwarePaginator
    {
        $query = Activity::query()
            ->causedBy($user)
            ->with('subject')
            ->latest();

        $this->applyFilters($query, $filters);

        $perPage = $filters['per_page'] ?? config('studio.activity_log.per_page', 25);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Filter by log name
        if (!empty($filters['log_name'])) {
            $query->inLog($filters['log_name']);
        }

        // Filter by event type
        if (!empty($filters['event'])) {
            $query->forEvent($filters['event']);
        }

        // Filter by subject type
        if (!empty($filters['subject_type'])) {
            $query->forSubjectType($filters['subject_type']);
        }

        // Filter by causer ID
        if (!empty($filters['causer_id'])) {
            $causerType = $filters['causer_type'] ?? config('studio.authorization.models.user', \App\Models\User::class);
            $query->where('causer_type', $causerType)
                  ->where('causer_id', $filters['causer_id']);
        }

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->betweenDates($filters['from_date'], $filters['to_date']);
        } elseif (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        } elseif (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        // Filter by batch UUID
        if (!empty($filters['batch_uuid'])) {
            $query->inBatch($filters['batch_uuid']);
        }

        // Search in description
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Get activity statistics.
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Activity::query();
        $this->applyFilters($query, $filters);

        return [
            'total' => (clone $query)->count(),
            'by_event' => (clone $query)
                ->selectRaw('event, count(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'by_log' => (clone $query)
                ->selectRaw('log_name, count(*) as count')
                ->groupBy('log_name')
                ->pluck('count', 'log_name')
                ->toArray(),
            'today' => (clone $query)->today()->count(),
        ];
    }

    /**
     * Get recent activities.
     */
    public function getRecentActivities(int $limit = 10, ?string $logName = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Activity::query()->with(['subject', 'causer'])->latest();

        if ($logName) {
            $query->inLog($logName);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Delete old activities.
     */
    public function cleanup(int $daysToKeep = null): int
    {
        $daysToKeep = $daysToKeep ?? config('studio.activity_log.cleanup_days', 90);

        if ($daysToKeep <= 0) {
            return 0;
        }

        $cutoffDate = now()->subDays($daysToKeep);

        return Activity::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Delete all activities for a specific subject.
     */
    public function deleteSubjectActivities(Model $subject): int
    {
        return Activity::forSubject($subject)->delete();
    }

    /**
     * Start a batch of activities.
     */
    public function startBatch(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Get all available log names.
     */
    public function getLogNames(): array
    {
        return Activity::query()
            ->distinct()
            ->pluck('log_name')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get all available event types.
     */
    public function getEventTypes(): array
    {
        return Activity::query()
            ->distinct()
            ->pluck('event')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Get all subject types that have activities.
     */
    public function getSubjectTypes(): array
    {
        return Activity::query()
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->map(fn($type) => [
                'type' => $type,
                'label' => class_basename($type),
            ])
            ->values()
            ->toArray();
    }
}
