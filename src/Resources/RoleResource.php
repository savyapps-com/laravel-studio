<?php

namespace SavyApps\LaravelStudio\Resources;

use SavyApps\LaravelStudio\Models\Role;
use SavyApps\LaravelStudio\Policies\RolePolicy;
use SavyApps\LaravelStudio\Resources\Actions\BulkDeleteAction;
use SavyApps\LaravelStudio\Resources\Fields\BelongsToMany;
use SavyApps\LaravelStudio\Resources\Fields\Section;
use SavyApps\LaravelStudio\Resources\Fields\Text;
use SavyApps\LaravelStudio\Resources\Fields\Textarea;
use SavyApps\LaravelStudio\Traits\Authorizable;

class RoleResource extends Resource
{
    use Authorizable;

    public static string $model = Role::class;

    public static string $policy = RolePolicy::class;

    public static string $label = 'Roles';

    public static string $singularLabel = 'Role';

    public static string $title = 'name';

    public static array $search = ['name', 'slug'];

    public static int $perPage = 15;

    /**
     * Fields shown in the index/table view.
     */
    public function indexFields(): array
    {
        return [
            Text::make('Name')->sortable()->searchable(),
            Text::make('Slug')->sortable()->searchable(),
            BelongsToMany::make('Users'),
        ];
    }

    /**
     * Fields shown in the detail/show view.
     */
    public function showFields(): array
    {
        return [
            Text::make('Name'),
            Text::make('Slug'),
            Textarea::make('Description'),
            BelongsToMany::make('Users'),
        ];
    }

    /**
     * Fields shown in create/edit forms.
     */
    public function formFields(): array
    {
        return [
            Section::make('Role Information')
                ->description('Define the role details')
                ->icon('shield')
                ->fields([
                    Text::make('Name')
                        ->rules('required|string|max:255')
                        ->placeholder('Enter role name')
                        ->cols('col-span-12 md:col-span-6'),

                    Text::make('Slug')
                        ->rules('required|string|max:255|unique:roles,slug')
                        ->placeholder('Enter role slug')
                        ->cols('col-span-12 md:col-span-6'),

                    Textarea::make('Description')
                        ->rules('nullable|string')
                        ->rows(4)
                        ->placeholder('Enter role description')
                        ->cols('col-span-12'),
                ]),

            Section::make('Users')
                ->description('Assign users to this role')
                ->icon('users')
                ->collapsible()
                ->fields([
                    BelongsToMany::make('Users')
                        ->resource(UserResource::class)
                        ->titleAttribute('name')
                        ->rules('nullable|array')
                        ->cols('col-span-12'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
        ];
    }

    public function with(): array
    {
        return ['users'];
    }
}
