<?php

namespace SavyApps\LaravelStudio\Resources;

use SavyApps\LaravelStudio\Cards\PartitionCard;
use SavyApps\LaravelStudio\Cards\TrendCard;
use SavyApps\LaravelStudio\Cards\ValueCard;
use SavyApps\LaravelStudio\Models\Role;
use SavyApps\LaravelStudio\Policies\UserPolicy;
use SavyApps\LaravelStudio\Resources\Actions\BulkDeleteAction;
use SavyApps\LaravelStudio\Resources\Actions\BulkUpdateAction;
use SavyApps\LaravelStudio\Resources\Fields\BelongsToMany;
use SavyApps\LaravelStudio\Resources\Fields\Date;
use SavyApps\LaravelStudio\Resources\Fields\Email;
use SavyApps\LaravelStudio\Resources\Fields\Media;
use SavyApps\LaravelStudio\Resources\Fields\Password;
use SavyApps\LaravelStudio\Resources\Fields\Section;
use SavyApps\LaravelStudio\Resources\Fields\Select;
use SavyApps\LaravelStudio\Resources\Fields\Text;
use SavyApps\LaravelStudio\Resources\Filters\BelongsToManyFilter;
use SavyApps\LaravelStudio\Resources\Filters\SelectFilter;
use SavyApps\LaravelStudio\Traits\Authorizable;

class UserResource extends Resource
{
    use Authorizable;

    public static string $policy = UserPolicy::class;

    public static string $label = 'Users';

    public static string $singularLabel = 'User';

    public static string $title = 'name';

    public static array $search = ['name', 'email'];

    public static int $perPage = 15;

    /**
     * Get the model class from config.
     */
    public static function model(): string
    {
        return config('studio.authorization.models.user', 'App\\Models\\User');
    }

    /**
     * Get status options. Override in application to use custom status enum.
     */
    protected function getStatusOptions(): array
    {
        // Check if the user model has a Status enum defined
        $userModel = static::model();

        // Default status options
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    /**
     * Fields shown in the index/table view.
     */
    public function indexFields(): array
    {
        return [
            Media::make('Avatar')
                ->collection('avatars')
                ->previewSize(48, 48)
                ->rounded(),
            Text::make('Name')->sortable()->searchable(),
            Email::make('Email')->sortable()->searchable(),
            Select::make('Status')->options($this->getStatusOptions())->sortable(),
            Date::make('Email Verified At', 'email_verified_at')->sortable(),
            BelongsToMany::make('Roles'),
        ];
    }

    /**
     * Fields shown in the detail/show view.
     */
    public function showFields(): array
    {
        return [
            Media::make('Avatar')
                ->collection('avatars')
                ->previewSize(64, 64)
                ->rounded(),
            Text::make('Name'),
            Email::make('Email'),
            Select::make('Status')->options($this->getStatusOptions()),
            BelongsToMany::make('Roles'),
            Date::make('Email Verified At'),
        ];
    }

    /**
     * Fields shown in create/edit forms.
     */
    public function formFields(): array
    {
        return [
            Section::make('Profile Information')
                ->description('Basic user profile details')
                ->icon('user')
                ->fields([
                    Media::make('Avatar')
                        ->single()
                        ->collection('avatars')
                        ->images()
                        ->maxFileSize(2)
                        ->previewSize(48, 48)
                        ->rounded()
                        ->editable([
                            'aspectRatio' => 1,
                            'minWidth' => 200,
                            'minHeight' => 200,
                        ])
                        ->rules('nullable')
                        ->cols('col-span-12'),

                    Text::make('Name')
                        ->rules('required|string|max:255')
                        ->placeholder('Enter user name')
                        ->cols('col-span-12 md:col-span-6'),

                    Email::make('Email')
                        ->rules('required|email|unique:users,email')
                        ->placeholder('Enter email address')
                        ->cols('col-span-12 md:col-span-6'),
                ]),

            Section::make('Security')
                ->description('Password and account status')
                ->icon('lock')
                ->fields([
                    Password::make('Password')
                        ->rules('required|string|min:8')
                        ->creationRules('required|string|min:8')
                        ->updateRules('nullable|string|min:8')
                        ->requiredOnCreate(true)
                        ->requiredOnUpdate(false)
                        ->creationPlaceholder('Enter password')
                        ->updatePlaceholder('Leave blank to keep current password')
                        ->cols('col-span-12 md:col-span-6'),

                    Select::make('Status')
                        ->options($this->getStatusOptions())
                        ->rules(['required', 'in:active,inactive'])
                        ->default('active')
                        ->cols('col-span-12 md:col-span-6'),
                ]),

            Section::make('Permissions')
                ->description('User roles and access control')
                ->icon('shield')
                ->collapsible()
                ->fields([
                    BelongsToMany::make('Roles')
                        ->resource(RoleResource::class)
                        ->titleAttribute('name')
                        ->creatable()
                        ->rules('nullable|array')
                        ->cols('col-span-12'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options($this->getStatusOptions())
                ->column('status'),

            BelongsToManyFilter::make('Role')
                ->options(fn () => Role::pluck('name', 'id')->toArray())
                ->relationship('roles'),
        ];
    }

    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
            BulkUpdateAction::make()->fields([
                'status' => $this->getStatusOptions(),
            ]),
        ];
    }

    public function with(): array
    {
        return ['roles'];
    }

    /**
     * Dashboard cards for the users resource.
     */
    public function cards(): array
    {
        $userModel = static::model();

        return [
            ValueCard::make('Total Users')
                ->value(fn () => $userModel::count())
                ->icon('users')
                ->color('blue')
                ->width('1/4'),

            TrendCard::make('New Users')
                ->value(fn () => $userModel::where('created_at', '>=', now()->subDays(30))->count())
                ->previousValue(fn () => $userModel::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count())
                ->comparisonLabel('vs last 30 days')
                ->icon('user-plus')
                ->color('green')
                ->width('1/4'),

            ValueCard::make('Active Users')
                ->value(fn () => $userModel::where('status', 'active')->count())
                ->icon('check-circle')
                ->color('teal')
                ->width('1/4'),

            PartitionCard::make('Users by Role')
                ->data(fn () => Role::withCount('users')->get()->pluck('users_count', 'name')->toArray())
                ->type('donut')
                ->width('1/4'),
        ];
    }

    /**
     * Override rules to handle context-specific password rules.
     */
    public function rules(string $context = 'create'): array
    {
        $rules = [];
        $formFields = $this->flattenFields($this->getFormFields());

        foreach ($formFields as $field) {
            // Check for context-specific rules in Password fields
            if ($field instanceof Password) {
                if ($context === 'create' && isset($field->toArray()['meta']['creationRules'])) {
                    $rules[$field->attribute] = $field->toArray()['meta']['creationRules'];
                } elseif ($context === 'update' && isset($field->toArray()['meta']['updateRules'])) {
                    $rules[$field->attribute] = $field->toArray()['meta']['updateRules'];
                } elseif ($field->rules) {
                    $rules[$field->attribute] = $field->rules;
                }
            } elseif ($field->rules) {
                $rules[$field->attribute] = $field->rules;
            }
        }

        return $rules;
    }
}
