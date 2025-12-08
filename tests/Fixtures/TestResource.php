<?php

namespace SavyApps\LaravelStudio\Tests\Fixtures;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\ID;
use SavyApps\LaravelStudio\Resources\Fields\Text;
use SavyApps\LaravelStudio\Resources\Fields\Email;
use SavyApps\LaravelStudio\Resources\Fields\Number;
use SavyApps\LaravelStudio\Resources\Fields\Boolean;
use SavyApps\LaravelStudio\Resources\Fields\Select;
use SavyApps\LaravelStudio\Resources\Fields\Date;
use SavyApps\LaravelStudio\Resources\Filters\SelectFilter;
use SavyApps\LaravelStudio\Resources\Filters\BooleanFilter;
use SavyApps\LaravelStudio\Resources\Actions\BulkDeleteAction;

class TestResource extends Resource
{
    public static string $model = TestModel::class;

    public static string $label = 'Test Resources';

    public static string $singularLabel = 'Test Resource';

    public static string $title = 'name';

    public static array $search = ['name', 'email'];

    public static int $perPage = 15;

    public function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Name')->sortable()->searchable(),
            Email::make('Email')->sortable(),
            Select::make('Status')->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
            ])->sortable(),
            Boolean::make('Is Active', 'is_active'),
            Date::make('Created At', 'created_at')->sortable(),
        ];
    }

    public function showFields(): array
    {
        return [
            ID::make(),
            Text::make('Name'),
            Email::make('Email'),
            Select::make('Status')->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
            ]),
            Number::make('Age'),
            Boolean::make('Is Active', 'is_active'),
            Date::make('Published At', 'published_at'),
            Date::make('Created At', 'created_at'),
            Date::make('Updated At', 'updated_at'),
        ];
    }

    public function formFields(): array
    {
        return [
            Text::make('Name')
                ->rules('required|string|max:255')
                ->placeholder('Enter name'),

            Email::make('Email')
                ->rules('required|email|unique:test_models,email')
                ->placeholder('Enter email'),

            Select::make('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->rules('required|in:active,inactive')
                ->default('active'),

            Number::make('Age')
                ->rules('nullable|integer|min:0|max:150'),

            Boolean::make('Is Active', 'is_active')
                ->rules('nullable|boolean')
                ->default(true),

            Date::make('Published At', 'published_at')
                ->rules('nullable|date'),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->column('status'),

            BooleanFilter::make('Is Active')
                ->column('is_active'),
        ];
    }

    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
        ];
    }
}
