<?php

namespace SavyApps\LaravelStudio\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

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
        'batch_uuid',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the subject of the activity.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer (user) of the activity.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by log name.
     */
    public function scopeInLog(Builder $query, string $logName): Builder
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter activities for a specific subject.
     */
    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope to filter activities caused by a specific user.
     */
    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    /**
     * Scope to filter activities for a specific subject type.
     */
    public function scopeForSubjectType(Builder $query, string $subjectType): Builder
    {
        return $query->where('subject_type', $subjectType);
    }

    /**
     * Scope to filter activities from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * Scope to filter activities from a date range.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by batch UUID.
     */
    public function scopeInBatch(Builder $query, string $batchUuid): Builder
    {
        return $query->where('batch_uuid', $batchUuid);
    }

    /**
     * Get the old values from properties.
     */
    public function getOldAttribute(): array
    {
        return Arr::get($this->properties, 'old', []);
    }

    /**
     * Get the new/current values from properties.
     */
    public function getNewAttribute(): array
    {
        return Arr::get($this->properties, 'attributes', []);
    }

    /**
     * Get the changes (diff between old and new).
     */
    public function getChangesAttribute(): array
    {
        $old = $this->old;
        $new = $this->new;
        $changes = [];

        // Get all keys from both old and new
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($keys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if a specific attribute changed.
     */
    public function attributeChanged(string $attribute): bool
    {
        return array_key_exists($attribute, $this->changes);
    }

    /**
     * Get formatted description.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $subjectName = $this->subject_type ? class_basename($this->subject_type) : 'Item';

        return match ($this->event) {
            'created' => "{$subjectName} was created",
            'updated' => "{$subjectName} was updated",
            'deleted' => "{$subjectName} was deleted",
            'restored' => "{$subjectName} was restored",
            default => ucfirst($this->event),
        };
    }

    /**
     * Create a new activity log builder.
     */
    public static function log(string $event): ActivityLogBuilder
    {
        return new ActivityLogBuilder($event);
    }
}

/**
 * Fluent builder for creating activity logs.
 */
class ActivityLogBuilder
{
    protected string $event;
    protected ?string $logName = 'default';
    protected ?string $description = null;
    protected ?Model $subject = null;
    protected ?Model $causer = null;
    protected array $properties = [];
    protected ?string $batchUuid = null;

    public function __construct(string $event)
    {
        $this->event = $event;
    }

    public function inLog(string $logName): self
    {
        $this->logName = $logName;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function performedOn(Model $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function causedBy(?Model $causer): self
    {
        $this->causer = $causer;
        return $this;
    }

    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    public function withProperty(string $key, $value): self
    {
        $this->properties[$key] = $value;
        return $this;
    }

    public function inBatch(string $batchUuid): self
    {
        $this->batchUuid = $batchUuid;
        return $this;
    }

    public function save(): Activity
    {
        $request = request();

        return Activity::create([
            'log_name' => $this->logName,
            'event' => $this->event,
            'description' => $this->description,
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'causer_type' => $this->causer?->getMorphClass(),
            'causer_id' => $this->causer?->getKey(),
            'properties' => !empty($this->properties) ? $this->properties : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'batch_uuid' => $this->batchUuid,
        ]);
    }
}
