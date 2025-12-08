# Activity/Audit Logs - Laravel Studio Package

## Overview

**Purpose:** Track all changes to resources for compliance, debugging, and accountability.

**Approach:** Custom-built with auto-logging trait
**Retention:** Forever (user decides when to clean via command)
**Dependencies:** None

---

## API Design

### Automatic Logging via Trait

```php
// In Model
use SavyApps\LaravelStudio\Traits\LogsActivity;

class User extends Model
{
    use LogsActivity;

    // Optional: Customize what attributes to log
    protected static $logAttributes = ['name', 'email', 'status'];

    // Optional: Only log changed attributes
    protected static $logOnlyDirty = true;

    // Optional: Custom log name for grouping
    protected static $logName = 'users';

    // Optional: Custom description
    public function getActivityDescription(string $event): string
    {
        return match ($event) {
            'created' => "Created user: {$this->name}",
            'updated' => "Updated user: {$this->name}",
            'deleted' => "Deleted user: {$this->name}",
            default => "{$event} user: {$this->name}",
        };
    }
}
```

### Manual Logging

```php
use SavyApps\LaravelStudio\Facades\Activity;

// Basic logging
Activity::log('user-login')
    ->causedBy($user)
    ->log('User logged in');

// With subject (model being acted on)
Activity::log('order-status-change')
    ->performedOn($order)
    ->causedBy($admin)
    ->withProperties([
        'old_status' => 'pending',
        'new_status' => 'approved',
    ])
    ->log('Order status changed');

// Custom event
Activity::log('export')
    ->causedBy($user)
    ->withProperties([
        'resource' => 'users',
        'format' => 'xlsx',
        'count' => 500,
    ])
    ->log('Exported users to Excel');
```

### Querying Activities

```php
use SavyApps\LaravelStudio\Models\Activity;

// Get activities for a specific model
Activity::forSubject($user)->latest()->get();

// Get activities by a specific user
Activity::causedBy($admin)->latest()->get();

// Get activities by log name
Activity::inLog('users')->get();

// Get activities by event type
Activity::where('event', 'updated')->get();

// Get today's activities
Activity::today()->get();

// Get activities in date range
Activity::whereBetween('created_at', [$startDate, $endDate])->get();

// Complex queries
Activity::query()
    ->inLog('orders')
    ->causedBy($admin)
    ->where('event', 'updated')
    ->whereBetween('created_at', [now()->subDays(7), now()])
    ->latest()
    ->paginate(20);
```

### Configuration

```php
// config/studio.php
'activity_log' => [
    // Enable/disable activity logging
    'enabled' => true,

    // Default log name
    'default_log_name' => 'default',

    // Table name
    'table_name' => 'activities',

    // Automatically log these events
    'auto_log_events' => ['created', 'updated', 'deleted'],

    // Exclude these attributes from logging
    'exclude_attributes' => ['password', 'remember_token', 'two_factor_secret'],

    // Log IP address
    'log_ip_address' => true,

    // Log user agent
    'log_user_agent' => true,

    // Queue activity logging for performance
    'queue' => false,
    'queue_name' => 'default',
],
```

---

## Database Schema

```php
// database/migrations/xxxx_create_activities_table.php
Schema::create('activities', function (Blueprint $table) {
    $table->id();
    $table->string('log_name')->default('default');
    $table->string('event');                        // created, updated, deleted, custom
    $table->text('description')->nullable();

    // The model being acted on (polymorphic)
    $table->nullableMorphs('subject');

    // The user performing the action (polymorphic)
    $table->nullableMorphs('causer');

    // Store old/new values and any custom data
    $table->json('properties')->nullable();

    // Request metadata
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();

    $table->timestamps();

    // Indexes for common queries
    $table->index('log_name');
    $table->index('event');
    $table->index('created_at');
    $table->index(['subject_type', 'subject_id']);
    $table->index(['causer_type', 'causer_id']);
});
```

---

## Backend Implementation

### File Structure

```
packages/laravel-studio/
├── src/
│   ├── Models/
│   │   └── Activity.php
│   ├── Services/
│   │   └── ActivityService.php
│   ├── Traits/
│   │   └── LogsActivity.php
│   ├── Facades/
│   │   └── Activity.php
│   ├── Http/Controllers/
│   │   └── ActivityController.php
│   ├── Resources/
│   │   └── ActivityResource.php
│   └── Console/Commands/
│       └── CleanupActivitiesCommand.php
├── database/migrations/
│   └── xxxx_create_activities_table.php
└── config/studio.php
```

### Activity Model

```php
// src/Models/Activity.php
namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class Activity extends Model
{
    protected $fillable = [
        'log_name',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('studio.activity_log.table_name', 'activities');
    }

    /**
     * The model that was acted upon
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who performed the action
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the old values from properties
     */
    public function getOldAttribute(): array
    {
        return $this->properties['old'] ?? [];
    }

    /**
     * Get the new values from properties
     */
    public function getNewAttribute(): array
    {
        return $this->properties['new'] ?? [];
    }

    /**
     * Get changed attributes (diff between old and new)
     */
    public function getChangesAttribute(): array
    {
        $old = $this->old;
        $new = $this->new;

        $changes = [];
        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    // Scopes

    public function scopeInLog(Builder $query, string $logName): Builder
    {
        return $query->where('log_name', $logName);
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }
}
```

### Activity Service (Fluent Builder)

```php
// src/Services/ActivityService.php
namespace SavyApps\LaravelStudio\Services;

use SavyApps\LaravelStudio\Models\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    protected ?string $logName = null;
    protected ?string $event = null;
    protected ?string $description = null;
    protected ?Model $subject = null;
    protected ?Model $causer = null;
    protected array $properties = [];

    /**
     * Set the log name
     */
    public function log(string $logName): self
    {
        $this->logName = $logName;
        return $this;
    }

    /**
     * Set the event type
     */
    public function event(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Set the model being acted upon
     */
    public function performedOn(Model $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the user performing the action
     */
    public function causedBy(?Model $causer): self
    {
        $this->causer = $causer ?? auth()->user();
        return $this;
    }

    /**
     * Set custom properties
     */
    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    /**
     * Set old values (for updates)
     */
    public function withOld(array $old): self
    {
        $this->properties['old'] = $old;
        return $this;
    }

    /**
     * Set new values (for updates)
     */
    public function withNew(array $new): self
    {
        $this->properties['new'] = $new;
        return $this;
    }

    /**
     * Save the activity log
     */
    public function save(?string $description = null): Activity
    {
        $activity = new Activity([
            'log_name' => $this->logName ?? config('studio.activity_log.default_log_name', 'default'),
            'event' => $this->event ?? 'custom',
            'description' => $description ?? $this->description,
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'causer_type' => $this->causer?->getMorphClass(),
            'causer_id' => $this->causer?->getKey(),
            'properties' => $this->properties ?: null,
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ]);

        $activity->save();

        $this->reset();

        return $activity;
    }

    /**
     * Alias for save()
     */
    public function log(string $description): Activity
    {
        return $this->save($description);
    }

    /**
     * Reset the builder state
     */
    protected function reset(): void
    {
        $this->logName = null;
        $this->event = null;
        $this->description = null;
        $this->subject = null;
        $this->causer = null;
        $this->properties = [];
    }

    protected function getIpAddress(): ?string
    {
        if (!config('studio.activity_log.log_ip_address', true)) {
            return null;
        }
        return request()->ip();
    }

    protected function getUserAgent(): ?string
    {
        if (!config('studio.activity_log.log_user_agent', true)) {
            return null;
        }
        return request()->userAgent();
    }
}
```

### LogsActivity Trait

```php
// src/Traits/LogsActivity.php
namespace SavyApps\LaravelStudio\Traits;

use SavyApps\LaravelStudio\Models\Activity;
use SavyApps\LaravelStudio\Facades\Activity as ActivityFacade;

trait LogsActivity
{
    protected static array $oldAttributes = [];

    public static function bootLogsActivity(): void
    {
        if (!config('studio.activity_log.enabled', true)) {
            return;
        }

        // Store old attributes before update
        static::updating(function ($model) {
            static::$oldAttributes[$model->getKey()] = $model->getOriginal();
        });

        // Log events
        foreach (static::getLogEvents() as $event) {
            static::$event(function ($model) use ($event) {
                $model->logActivity($event);
            });
        }
    }

    /**
     * Log an activity for this model
     */
    public function logActivity(string $event): void
    {
        $properties = $this->getActivityProperties($event);

        ActivityFacade::log($this->getLogName())
            ->event($event)
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->save($this->getActivityDescription($event));
    }

    /**
     * Get the log name for this model
     */
    protected function getLogName(): string
    {
        return static::$logName ?? strtolower(class_basename($this));
    }

    /**
     * Get events to log
     */
    protected static function getLogEvents(): array
    {
        return static::$logEvents ?? config('studio.activity_log.auto_log_events', ['created', 'updated', 'deleted']);
    }

    /**
     * Get attributes to log
     */
    protected function getLogAttributes(): array
    {
        if (isset(static::$logAttributes)) {
            return static::$logAttributes;
        }

        // Default: all fillable except excluded
        $excluded = config('studio.activity_log.exclude_attributes', []);
        return array_diff($this->getFillable(), $excluded);
    }

    /**
     * Get activity properties based on event
     */
    protected function getActivityProperties(string $event): array
    {
        $attributes = $this->getLogAttributes();

        return match ($event) {
            'created' => [
                'new' => $this->only($attributes),
            ],
            'updated' => [
                'old' => collect(static::$oldAttributes[$this->getKey()] ?? [])->only($attributes)->toArray(),
                'new' => $this->only($attributes),
            ],
            'deleted' => [
                'old' => $this->only($attributes),
            ],
            default => [],
        };
    }

    /**
     * Get the description for the activity
     */
    public function getActivityDescription(string $event): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getKey();

        return match ($event) {
            'created' => "{$modelName} #{$identifier} was created",
            'updated' => "{$modelName} #{$identifier} was updated",
            'deleted' => "{$modelName} #{$identifier} was deleted",
            default => "{$modelName} #{$identifier}: {$event}",
        };
    }

    /**
     * Get activities for this model
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Get the last activity for this model
     */
    public function lastActivity()
    {
        return $this->activities()->latest()->first();
    }
}
```

### Activity Controller

```php
// src/Http/Controllers/ActivityController.php
namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SavyApps\LaravelStudio\Models\Activity;

class ActivityController extends Controller
{
    /**
     * List activities with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['subject', 'causer'])
            ->latest();

        // Filter by log name
        if ($request->filled('log')) {
            $query->inLog($request->log);
        }

        // Filter by event type
        if ($request->filled('event')) {
            $query->forEvent($request->event);
        }

        // Filter by causer (user)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                  ->where('causer_type', config('auth.providers.users.model'));
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $activities = $query->paginate($request->get('per_page', 20));

        return response()->json($activities);
    }

    /**
     * Get activities for a specific model
     */
    public function forSubject(Request $request, string $type, $id): JsonResponse
    {
        $modelClass = $this->resolveModelClass($type);
        $model = $modelClass::findOrFail($id);

        $activities = Activity::forSubject($model)
            ->with('causer')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($activities);
    }

    /**
     * Get activity statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        return response()->json([
            'total' => Activity::count(),
            'today' => Activity::today()->count(),
            'this_week' => Activity::thisWeek()->count(),
            'by_event' => Activity::selectRaw('event, count(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event'),
            'by_day' => Activity::selectRaw('DATE(created_at) as date, count(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
        ]);
    }

    protected function resolveModelClass(string $type): string
    {
        // Map resource types to model classes
        $resources = config('studio.resources', []);

        if (isset($resources[$type])) {
            return $resources[$type]::model();
        }

        throw new \InvalidArgumentException("Unknown resource type: {$type}");
    }
}
```

### Cleanup Command

```php
// src/Console/Commands/CleanupActivitiesCommand.php
namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use SavyApps\LaravelStudio\Models\Activity;

class CleanupActivitiesCommand extends Command
{
    protected $signature = 'studio:cleanup-activities
                            {--days=90 : Delete activities older than this many days}
                            {--log= : Only cleanup specific log name}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Clean up old activity logs';

    public function handle(): int
    {
        $days = $this->option('days');
        $logName = $this->option('log');
        $dryRun = $this->option('dry-run');

        $query = Activity::where('created_at', '<', now()->subDays($days));

        if ($logName) {
            $query->inLog($logName);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No activities to clean up.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} activities older than {$days} days.");
            return Command::SUCCESS;
        }

        if (!$this->confirm("Delete {$count} activities older than {$days} days?")) {
            $this->info('Cleanup cancelled.');
            return Command::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Deleted {$deleted} activities.");

        return Command::SUCCESS;
    }
}
```

---

## Frontend Implementation

### File Structure

```
packages/laravel-studio/resources/js/
├── components/
│   └── activity/
│       ├── ActivityTimeline.vue
│       ├── ActivityItem.vue
│       ├── ActivityDiff.vue
│       ├── ActivityFilters.vue
│       └── SubjectActivityPanel.vue
├── composables/
│   └── useActivityLog.js
├── services/
│   └── activityService.js
└── pages/
    └── ActivityLog.vue
```

### Activity Service

```javascript
// services/activityService.js
import api from '@/services/api'

export const activityService = {
  /**
   * Get paginated activities with filters
   */
  async getAll(params = {}) {
    const response = await api.get('/api/activities', { params })
    return response.data
  },

  /**
   * Get activities for a specific subject
   */
  async forSubject(type, id, params = {}) {
    const response = await api.get(`/api/activities/${type}/${id}`, { params })
    return response.data
  },

  /**
   * Get activity statistics
   */
  async getStats(days = 30) {
    const response = await api.get('/api/activities/stats', { params: { days } })
    return response.data
  },
}

export default activityService
```

### Activity Timeline Component

```vue
<!-- components/activity/ActivityTimeline.vue -->
<script setup>
import { ref, onMounted, watch } from 'vue'
import { activityService } from '@/services/activityService'
import ActivityItem from './ActivityItem.vue'
import ActivityFilters from './ActivityFilters.vue'

const props = defineProps({
  // Optional: filter by subject
  subjectType: String,
  subjectId: [String, Number],
  // Show filters
  showFilters: {
    type: Boolean,
    default: true,
  },
})

const activities = ref([])
const loading = ref(false)
const pagination = ref({})
const filters = ref({
  log: '',
  event: '',
  causer_id: '',
  from: '',
  to: '',
  search: '',
})

const loadActivities = async (page = 1) => {
  loading.value = true
  try {
    let response

    if (props.subjectType && props.subjectId) {
      response = await activityService.forSubject(
        props.subjectType,
        props.subjectId,
        { ...filters.value, page }
      )
    } else {
      response = await activityService.getAll({ ...filters.value, page })
    }

    activities.value = response.data
    pagination.value = {
      currentPage: response.current_page,
      lastPage: response.last_page,
      total: response.total,
    }
  } finally {
    loading.value = false
  }
}

const handleFilterChange = (newFilters) => {
  filters.value = { ...filters.value, ...newFilters }
  loadActivities(1)
}

onMounted(() => loadActivities())

watch(() => [props.subjectType, props.subjectId], () => {
  loadActivities()
})
</script>

<template>
  <div class="activity-timeline">
    <ActivityFilters
      v-if="showFilters"
      :filters="filters"
      @change="handleFilterChange"
    />

    <div v-if="loading" class="loading">
      Loading activities...
    </div>

    <div v-else-if="activities.length === 0" class="empty">
      No activities found.
    </div>

    <div v-else class="timeline">
      <ActivityItem
        v-for="activity in activities"
        :key="activity.id"
        :activity="activity"
      />
    </div>

    <!-- Pagination -->
    <div v-if="pagination.lastPage > 1" class="pagination">
      <button
        :disabled="pagination.currentPage === 1"
        @click="loadActivities(pagination.currentPage - 1)"
      >
        Previous
      </button>
      <span>Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span>
      <button
        :disabled="pagination.currentPage === pagination.lastPage"
        @click="loadActivities(pagination.currentPage + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>
```

### Activity Item Component

```vue
<!-- components/activity/ActivityItem.vue -->
<script setup>
import { computed } from 'vue'
import ActivityDiff from './ActivityDiff.vue'

const props = defineProps({
  activity: {
    type: Object,
    required: true,
  },
})

const eventIcon = computed(() => {
  const icons = {
    created: 'plus-circle',
    updated: 'edit',
    deleted: 'trash',
    custom: 'activity',
  }
  return icons[props.activity.event] || 'activity'
})

const eventColor = computed(() => {
  const colors = {
    created: 'text-green-500',
    updated: 'text-blue-500',
    deleted: 'text-red-500',
    custom: 'text-gray-500',
  }
  return colors[props.activity.event] || 'text-gray-500'
})

const formattedDate = computed(() => {
  return new Date(props.activity.created_at).toLocaleString()
})

const hasChanges = computed(() => {
  return props.activity.properties?.old || props.activity.properties?.new
})
</script>

<template>
  <div class="activity-item">
    <div class="activity-icon" :class="eventColor">
      <Icon :name="eventIcon" />
    </div>

    <div class="activity-content">
      <div class="activity-header">
        <span class="activity-description">
          {{ activity.description }}
        </span>
        <span class="activity-time">
          {{ formattedDate }}
        </span>
      </div>

      <div v-if="activity.causer" class="activity-causer">
        by {{ activity.causer.name || 'System' }}
      </div>

      <ActivityDiff
        v-if="hasChanges"
        :old-values="activity.properties?.old"
        :new-values="activity.properties?.new"
      />

      <div v-if="activity.ip_address" class="activity-meta">
        IP: {{ activity.ip_address }}
      </div>
    </div>
  </div>
</template>
```

### Activity Diff Component

```vue
<!-- components/activity/ActivityDiff.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  oldValues: Object,
  newValues: Object,
})

const changes = computed(() => {
  const result = []
  const old = props.oldValues || {}
  const newVals = props.newValues || {}

  // Find all unique keys
  const allKeys = new Set([...Object.keys(old), ...Object.keys(newVals)])

  for (const key of allKeys) {
    const oldVal = old[key]
    const newVal = newVals[key]

    if (oldVal !== newVal) {
      result.push({
        field: key,
        old: oldVal,
        new: newVal,
      })
    }
  }

  return result
})

const formatValue = (value) => {
  if (value === null || value === undefined) return '(empty)'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}
</script>

<template>
  <div v-if="changes.length" class="activity-diff">
    <div
      v-for="change in changes"
      :key="change.field"
      class="diff-row"
    >
      <span class="diff-field">{{ change.field }}:</span>
      <span class="diff-old">{{ formatValue(change.old) }}</span>
      <span class="diff-arrow">→</span>
      <span class="diff-new">{{ formatValue(change.new) }}</span>
    </div>
  </div>
</template>

<style scoped>
.activity-diff {
  @apply mt-2 p-2 bg-gray-50 rounded text-sm;
}
.diff-row {
  @apply flex items-center gap-2 py-1;
}
.diff-field {
  @apply font-medium text-gray-700;
}
.diff-old {
  @apply text-red-600 line-through;
}
.diff-arrow {
  @apply text-gray-400;
}
.diff-new {
  @apply text-green-600;
}
</style>
```

---

## Integration with ResourceService

```php
// In ResourceService - Auto-log CRUD operations
public function create(Resource $resource, array $data): Model
{
    $model = $resource->model()::create($data);

    // Activity is auto-logged via LogsActivity trait
    // Or manually if model doesn't use trait:
    if (!in_array(LogsActivity::class, class_uses_recursive($model))) {
        Activity::log($resource->uriKey())
            ->event('created')
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->withNew($model->toArray())
            ->save("{$resource->singularLabel()} was created");
    }

    return $model;
}
```

---

## Routes

```php
// Register in ServiceProvider
Route::prefix('api/activities')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::get('/stats', [ActivityController::class, 'stats']);
        Route::get('/{type}/{id}', [ActivityController::class, 'forSubject']);
    });
```

---

## Artisan Commands

```bash
# Clean up activities older than 90 days
php artisan studio:cleanup-activities

# Clean up activities older than 30 days
php artisan studio:cleanup-activities --days=30

# Only clean up a specific log
php artisan studio:cleanup-activities --days=30 --log=users

# Dry run - see what would be deleted
php artisan studio:cleanup-activities --days=30 --dry-run
```

---

## Implementation Checklist

### Backend
- [ ] Create Activity model
- [ ] Create migration
- [ ] Create ActivityService (fluent builder)
- [ ] Create Activity facade
- [ ] Create LogsActivity trait
- [ ] Create ActivityController
- [ ] Create CleanupActivitiesCommand
- [ ] Add routes
- [ ] Update config/studio.php
- [ ] Register in ServiceProvider
- [ ] Integrate with ResourceService

### Frontend
- [ ] Create activityService.js
- [ ] Create ActivityTimeline component
- [ ] Create ActivityItem component
- [ ] Create ActivityDiff component
- [ ] Create ActivityFilters component
- [ ] Create Activity log page
- [ ] Add to admin navigation

### Testing
- [ ] Unit tests for LogsActivity trait
- [ ] Unit tests for ActivityService
- [ ] Feature tests for ActivityController
- [ ] Test cleanup command
