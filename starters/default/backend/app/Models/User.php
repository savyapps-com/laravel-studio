<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use SavyApps\LaravelStudio\Models\Role;
use SavyApps\LaravelStudio\Traits\HasPermissions;
use SavyApps\LaravelStudio\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasPermissions, InteractsWithMedia, LogsActivity, Notifiable;

    /**
     * Activity logging configuration.
     * Logs changes to these attributes automatically.
     */
    protected static array $logAttributes = ['name', 'email', 'status'];
    protected static bool $logOnlyDirty = true;
    protected static string $logName = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'can_access_admin_panel',
        'is_admin',
        'is_user',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => Status::class,
            'is_admin' => 'boolean',
            'is_user' => 'boolean',
            'can_access_admin_panel' => 'boolean',
        ];
    }

    /**
     * Send the password reset notification using database template.
     * Retrieves the panel from cache (set by AuthService) to generate panel-aware URL.
     */
    public function sendPasswordResetNotification($token): void
    {
        // Retrieve the panel from cache (set by AuthService::sendPasswordResetLink)
        // Default to 'admin' if not found
        $panel = Cache::pull("password_reset_panel:{$this->email}", 'admin');

        // Generate panel-aware reset URL
        $resetUrl = config('app.url')."/{$panel}/reset-password?token={$token}&email=".urlencode($this->email);

        $this->notify(new \App\Notifications\TemplatedNotification('password_reset', [
            'reset_url' => $resetUrl,
            'token' => $token,
            'panel' => $panel,
        ]));
    }

    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settable');
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        return $setting ? $setting->getTypedValue() : $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $setting = $this->settings()->where('key', $key)->first();
        if ($setting) {
            $setting->setTypedValue($value);
            $setting->save();
        }
    }

    public function getSettingsByGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return $this->settings()->where('group', $group)->get();
    }

    public function llmConfigs(): HasMany
    {
        return $this->hasMany(UserLlmConfig::class);
    }

    public function llmConversations(): HasMany
    {
        return $this->hasMany(LlmConversation::class);
    }

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Get the user's primary role (first assigned role).
     */
    public function primaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has all of the given roles.
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        $roleCount = $this->roles()->whereIn('slug', $roleSlugs)->count();

        return $roleCount === count($roleSlugs);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is an admin (has admin or super_admin role).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Check if user is a regular user (has user role).
     */
    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Check if user can access admin panel.
     * Requires admin or super_admin role.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the can_access_admin_panel attribute.
     */
    public function getCanAccessAdminPanelAttribute(): bool
    {
        return $this->canAccessAdminPanel();
    }

    /**
     * Get the is_admin attribute.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the is_user attribute.
     */
    public function getIsUserAttribute(): bool
    {
        return $this->isUser();
    }

    /**
     * Assign a role to the user (adds to existing roles).
     */
    public function assignRole(int|string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        } elseif (is_int($role)) {
            $role = Role::findOrFail($role);
        }

        if (! $this->hasRole($role->slug)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Assign multiple roles to the user.
     */
    public function assignRoles(array $roles): void
    {
        foreach ($roles as $role) {
            $this->assignRole($role);
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(int|string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
        } elseif (is_int($role)) {
            $role = Role::find($role);
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Sync roles for the user (replaces all existing roles).
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = [];
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('slug', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            } elseif (is_int($role)) {
                $roleIds[] = $role;
            } elseif ($role instanceof Role) {
                $roleIds[] = $role->id;
            }
        }

        $this->roles()->sync($roleIds);
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === Status::Active;
    }

    /**
     * Check if user is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === Status::Inactive;
    }

    /**
     * Activate the user.
     */
    public function activate(): void
    {
        $this->update(['status' => Status::Active]);
    }

    /**
     * Deactivate the user.
     */
    public function deactivate(): void
    {
        $this->update(['status' => Status::Inactive]);
    }



    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(48)
                    ->height(48)
                    ->sharpen(10);

                $this->addMediaConversion('medium')
                    ->width(200)
                    ->height(200)
                    ->sharpen(10);
            });
    }
}
