<?php

namespace SavyApps\LaravelStudio\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use SavyApps\LaravelStudio\Models\Activity;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    public static function bootLogsActivity(): void
    {
        // Skip if activity logging is disabled globally
        if (!config('studio.activity_log.enabled', true)) {
            return;
        }

        static::created(function (Model $model) {
            if ($model->shouldLogActivity('created')) {
                $model->logActivity('created');
            }
        });

        static::updated(function (Model $model) {
            if ($model->shouldLogActivity('updated') && $model->wasChanged()) {
                $model->logActivity('updated');
            }
        });

        static::deleted(function (Model $model) {
            if ($model->shouldLogActivity('deleted')) {
                $model->logActivity('deleted');
            }
        });

        // Handle soft deletes if the model uses SoftDeletes
        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                if ($model->shouldLogActivity('restored')) {
                    $model->logActivity('restored');
                }
            });
        }
    }

    /**
     * Get the activities for this model.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    /**
     * Log an activity for this model.
     */
    public function logActivity(string $event, array $properties = []): Activity
    {
        $logName = $this->getActivityLogName();
        $causer = $this->getActivityCauser();

        // Build properties based on event
        $activityProperties = $this->buildActivityProperties($event, $properties);

        return Activity::log($event)
            ->inLog($logName)
            ->performedOn($this)
            ->causedBy($causer)
            ->withProperties($activityProperties)
            ->withDescription($this->getActivityDescription($event))
            ->save();
    }

    /**
     * Get the log name for this model.
     */
    protected function getActivityLogName(): string
    {
        if (property_exists($this, 'logName')) {
            return static::$logName;
        }

        return $this->getTable();
    }

    /**
     * Get the causer for the activity.
     */
    protected function getActivityCauser(): ?Model
    {
        return Auth::user();
    }

    /**
     * Get the description for the activity.
     */
    protected function getActivityDescription(string $event): string
    {
        if (method_exists($this, 'getActivityDescriptionFor')) {
            $description = $this->getActivityDescriptionFor($event);
            if ($description !== null) {
                return $description;
            }
        }

        // Generate default description based on event and model
        $modelName = class_basename($this);
        $title = $this->getActivitySubjectTitle();

        return match($event) {
            'created' => "{$modelName} \"{$title}\" was created",
            'updated' => "{$modelName} \"{$title}\" was updated",
            'deleted' => "{$modelName} \"{$title}\" was deleted",
            'restored' => "{$modelName} \"{$title}\" was restored",
            default => "{$modelName} \"{$title}\" - {$event}",
        };
    }

    /**
     * Get the title/name of the subject for the activity description.
     */
    protected function getActivitySubjectTitle(): string
    {
        // Try common title fields
        foreach (['name', 'title', 'label', 'email', 'id'] as $field) {
            if (isset($this->{$field})) {
                return (string) $this->{$field};
            }
        }

        return (string) $this->getKey();
    }

    /**
     * Build the properties array for the activity.
     */
    protected function buildActivityProperties(string $event, array $customProperties = []): array
    {
        $properties = [];

        switch ($event) {
            case 'created':
                $properties['attributes'] = $this->getLoggedAttributes();
                break;

            case 'updated':
                $properties['old'] = $this->getOldLoggedAttributes();
                $properties['attributes'] = $this->getChangedLoggedAttributes();
                break;

            case 'deleted':
                $properties['old'] = $this->getLoggedAttributes();
                break;

            case 'restored':
                $properties['attributes'] = $this->getLoggedAttributes();
                break;
        }

        return array_merge($properties, $customProperties);
    }

    /**
     * Get the attributes that should be logged.
     */
    protected function getLoggedAttributes(): array
    {
        $attributes = $this->getAttributes();
        $logAttributes = $this->getLogAttributeNames();
        $ignoreAttributes = $this->getIgnoreAttributeNames();

        // If specific attributes are defined, only log those
        if (!empty($logAttributes)) {
            $attributes = array_intersect_key($attributes, array_flip($logAttributes));
        }

        // Remove ignored attributes
        if (!empty($ignoreAttributes)) {
            $attributes = array_diff_key($attributes, array_flip($ignoreAttributes));
        }

        // Remove sensitive attributes
        $attributes = $this->removeSensitiveAttributes($attributes);

        return $attributes;
    }

    /**
     * Get old values of logged attributes before update.
     */
    protected function getOldLoggedAttributes(): array
    {
        $original = $this->getOriginal();
        $changed = $this->getChanges();
        $logAttributes = $this->getLogAttributeNames();
        $ignoreAttributes = $this->getIgnoreAttributeNames();

        // Get only the original values of changed attributes
        $oldValues = array_intersect_key($original, $changed);

        // If specific attributes are defined, filter
        if (!empty($logAttributes)) {
            $oldValues = array_intersect_key($oldValues, array_flip($logAttributes));
        }

        // Remove ignored attributes
        if (!empty($ignoreAttributes)) {
            $oldValues = array_diff_key($oldValues, array_flip($ignoreAttributes));
        }

        return $this->removeSensitiveAttributes($oldValues);
    }

    /**
     * Get changed attributes for logging.
     */
    protected function getChangedLoggedAttributes(): array
    {
        $changed = $this->getChanges();
        $logAttributes = $this->getLogAttributeNames();
        $ignoreAttributes = $this->getIgnoreAttributeNames();

        // If specific attributes are defined, filter
        if (!empty($logAttributes)) {
            $changed = array_intersect_key($changed, array_flip($logAttributes));
        }

        // Remove ignored attributes
        if (!empty($ignoreAttributes)) {
            $changed = array_diff_key($changed, array_flip($ignoreAttributes));
        }

        return $this->removeSensitiveAttributes($changed);
    }

    /**
     * Get the attribute names that should be logged.
     */
    protected function getLogAttributeNames(): array
    {
        if (property_exists($this, 'logAttributes')) {
            return static::$logAttributes;
        }

        return [];
    }

    /**
     * Get the attribute names that should be ignored.
     */
    protected function getIgnoreAttributeNames(): array
    {
        if (property_exists($this, 'logAttributesIgnore')) {
            return static::$logAttributesIgnore;
        }

        // Default ignored attributes
        return ['password', 'remember_token', 'updated_at'];
    }

    /**
     * Remove sensitive attributes from the array.
     */
    protected function removeSensitiveAttributes(array $attributes): array
    {
        $sensitiveAttributes = ['password', 'secret', 'token', 'api_key', 'private_key'];

        foreach ($sensitiveAttributes as $sensitive) {
            if (isset($attributes[$sensitive])) {
                $attributes[$sensitive] = '[REDACTED]';
            }
        }

        return $attributes;
    }

    /**
     * Determine if the activity should be logged.
     */
    protected function shouldLogActivity(string $event): bool
    {
        // Check if logging is enabled for this event
        if (property_exists($this, 'logEvents')) {
            if (!in_array($event, static::$logEvents)) {
                return false;
            }
        }

        // Check if we should only log dirty (changed) attributes
        if ($event === 'updated' && property_exists($this, 'logOnlyDirty') && static::$logOnlyDirty) {
            $logAttributes = $this->getLogAttributeNames();
            $changed = array_keys($this->getChanges());

            if (!empty($logAttributes)) {
                // Only log if any of the specified attributes changed
                return !empty(array_intersect($logAttributes, $changed));
            }
        }

        return true;
    }

    /**
     * Temporarily disable activity logging.
     */
    public function withoutLogging(callable $callback)
    {
        $originalValue = config('studio.activity_log.enabled');
        config(['studio.activity_log.enabled' => false]);

        try {
            return $callback($this);
        } finally {
            config(['studio.activity_log.enabled' => $originalValue]);
        }
    }
}
