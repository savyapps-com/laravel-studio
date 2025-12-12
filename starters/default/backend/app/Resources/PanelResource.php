<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Models\Panel;
use SavyApps\LaravelStudio\Resources\Actions\BulkDeleteAction;
use SavyApps\LaravelStudio\Resources\Fields\Boolean;
use SavyApps\LaravelStudio\Resources\Fields\IconPicker;
use SavyApps\LaravelStudio\Resources\Fields\Json;
use SavyApps\LaravelStudio\Resources\Fields\MultiSelectServer;
use SavyApps\LaravelStudio\Resources\Fields\Number;
use SavyApps\LaravelStudio\Resources\Fields\Section;
use SavyApps\LaravelStudio\Resources\Fields\TagInput;
use SavyApps\LaravelStudio\Resources\Fields\Text;
use SavyApps\LaravelStudio\Resources\Fields\Textarea;
use SavyApps\LaravelStudio\Resources\Filters\BooleanFilter;
use SavyApps\LaravelStudio\Resources\Resource;

class PanelResource extends Resource
{
    public static string $model = Panel::class;

    public static string $label = 'Panels';

    public static string $singularLabel = 'Panel';

    public static string $title = 'label';

    public static array $search = ['key', 'label', 'path'];

    public static int $perPage = 15;

    /**
     * Fields shown in the index/table view.
     */
    public function indexFields(): array
    {
        return [
            Text::make('Key')->sortable()->searchable(),
            Text::make('Label')->sortable()->searchable(),
            Text::make('Path')->sortable(),
            Text::make('Icon'),
            Number::make('Priority')->sortable(),
            Boolean::make('Active', 'is_active')->sortable()->toggleable(),
            Boolean::make('Default', 'is_default')->sortable()->toggleable(),
            Boolean::make('Registration', 'allow_registration')->sortable()->toggleable(),
        ];
    }

    /**
     * Fields shown in the detail/show view.
     */
    public function showFields(): array
    {
        return [
            Text::make('Key'),
            Text::make('Label'),
            Text::make('Path'),
            Text::make('Icon'),
            Number::make('Priority'),
            Boolean::make('Active', 'is_active'),
            Boolean::make('Default', 'is_default'),
            Boolean::make('Allow Registration', 'allow_registration'),
            Text::make('Default Role', 'default_role'),
            Json::make('Roles'),
            Json::make('Middleware'),
            Json::make('Resources'),
            Json::make('Features'),
            Json::make('Menu'),
            Json::make('Settings'),
        ];
    }

    /**
     * Fields shown in create/edit forms.
     */
    public function formFields(): array
    {
        return [
            Section::make('Basic Information')
                ->description('Core panel configuration')
                ->icon('layout')
                ->fields([
                    Text::make('Key')
                        ->rules('required|string|max:50|unique:panels,key')
                        ->placeholder('e.g., admin, user, vendor')
                        ->help('Unique identifier for the panel (lowercase, no spaces)')
                        ->cols('col-span-12 md:col-span-6'),

                    Text::make('Label')
                        ->rules('required|string|max:100')
                        ->placeholder('e.g., Admin Panel')
                        ->help('Display name for the panel')
                        ->cols('col-span-12 md:col-span-6'),

                    Text::make('Path')
                        ->rules('required|string|max:100')
                        ->placeholder('e.g., /admin')
                        ->help('URL path prefix for the panel')
                        ->cols('col-span-12 md:col-span-6'),

                    IconPicker::make('Icon')
                        ->rules('nullable|string|max:50')
                        ->searchable()
                        ->help('Icon for the panel')
                        ->cols('col-span-12 md:col-span-6'),

                    Number::make('Priority')
                        ->rules('nullable|integer|min:0')
                        ->default(100)
                        ->help('Display order (lower = higher priority)')
                        ->cols('col-span-12 md:col-span-6'),
                ]),

            Section::make('Access Control')
                ->description('Configure who can access this panel')
                ->icon('shield')
                ->fields([
                    TagInput::make('Roles')
                        ->rules('nullable|array')
                        ->suggestions(['admin', 'user', 'manager', 'editor', 'moderator'])
                        ->allowCustom()
                        ->help('Roles that can access this panel')
                        ->cols('col-span-12 md:col-span-6'),

                    TagInput::make('Middleware')
                        ->rules('nullable|array')
                        ->suggestions(['auth', 'verified', 'admin', 'api', 'throttle:60,1'])
                        ->allowCustom()
                        ->help('Middleware to apply to this panel')
                        ->cols('col-span-12 md:col-span-6'),
                ]),

            Section::make('Registration Settings')
                ->description('Control user registration for this panel')
                ->icon('user-plus')
                ->fields([
                    Boolean::make('Allow Registration', 'allow_registration')
                        ->rules('boolean')
                        ->default(false)
                        ->help('Allow users to self-register for this panel')
                        ->cols('col-span-12 md:col-span-6'),

                    Text::make('Default Role', 'default_role')
                        ->rules('nullable|string|max:50')
                        ->placeholder('e.g., user, member')
                        ->help('Role automatically assigned to users who register via this panel')
                        ->cols('col-span-12 md:col-span-6'),
                ]),

            Section::make('Panel Content')
                ->description('Resources and features available in this panel')
                ->icon('squares-2x2')
                ->collapsible()
                ->fields([
                    MultiSelectServer::make('Resources')
                        ->endpoint('/api/admin/panel-management/available-resources')
                        ->labelKey('label')
                        ->valueKey('key')
                        ->descriptionKey('description')
                        ->help('Resources available in this panel')
                        ->cols('col-span-12 md:col-span-6'),

                    MultiSelectServer::make('Features')
                        ->endpoint('/api/admin/panel-management/available-features')
                        ->labelKey('label')
                        ->valueKey('key')
                        ->descriptionKey('description')
                        ->help('Features enabled for this panel')
                        ->cols('col-span-12 md:col-span-6'),

                    Json::make('Menu')
                        ->rules('nullable|array')
                        ->help('Menu structure configuration (JSON array)')
                        ->cols('col-span-12'),
                ]),

            Section::make('Settings')
                ->description('Panel appearance and behavior settings')
                ->icon('settings')
                ->collapsible()
                ->fields([
                    Json::make('Settings')
                        ->rules('nullable|array')
                        ->help('Panel-specific settings like layout, theme (JSON object)')
                        ->cols('col-span-12'),

                    Boolean::make('Active', 'is_active')
                        ->rules('boolean')
                        ->default(true)
                        ->help('Whether this panel is active and accessible')
                        ->cols('col-span-12 md:col-span-6'),

                    Boolean::make('Default', 'is_default')
                        ->rules('boolean')
                        ->default(false)
                        ->help('Set as the default panel for users')
                        ->cols('col-span-12 md:col-span-6'),
                ]),
        ];
    }

    /**
     * Filters for the index view.
     */
    public function filters(): array
    {
        return [
            BooleanFilter::make('Active', 'is_active')
                ->column('is_active'),

            BooleanFilter::make('Default', 'is_default')
                ->column('is_default'),
        ];
    }

    /**
     * Available actions.
     */
    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
        ];
    }

    /**
     * Override rules to handle unique key validation on update.
     */
    public function rules(string $context = 'create'): array
    {
        $rules = parent::rules($context);

        // Handle unique key rule - this will be modified by the controller for updates
        if ($context === 'update') {
            $rules['key'] = 'required|string|max:50';
        }

        return $rules;
    }
}
