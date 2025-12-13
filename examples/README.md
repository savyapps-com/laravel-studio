# Laravel Studio Examples

This directory contains example code patterns for Laravel Studio.

## Directory Structure

```
examples/
├── resources/      # Resource class examples
├── fields/         # Field usage examples
├── actions/        # Custom action examples
└── filters/        # Custom filter examples
```

## Resources

### Basic Resource

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, Email, Boolean};

class UserResource extends Resource
{
    public static string $model = \App\Models\User::class;
    public static string $title = 'name';
    public static array $search = ['name', 'email'];

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable()->searchable(),
            Email::make('Email')->sortable(),
            Boolean::make('Active')->toggleable(),
        ];
    }

    public function formFields(): array
    {
        return [
            Text::make('Name')->rules('required', 'max:255'),
            Email::make('Email')->rules('required', 'email', 'unique:users,email'),
        ];
    }
}
```

### Resource with Relationships

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, BelongsTo, HasMany, BelongsToMany};

class PostResource extends Resource
{
    public static string $model = \App\Models\Post::class;
    public static string $title = 'title';

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Title')->sortable()->searchable(),
            BelongsTo::make('Author', 'user')->titleAttribute('name'),
        ];
    }

    public function formFields(): array
    {
        return [
            Text::make('Title')->rules('required'),
            BelongsTo::make('Author', 'user')
                ->searchable()
                ->rules('required'),
            BelongsToMany::make('Tags')
                ->titleAttribute('name'),
            HasMany::make('Comments'),
        ];
    }
}
```

### Resource with Sections

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{Text, Email, Password, Boolean, Section, Group};

class UserResource extends Resource
{
    public function formFields(): array
    {
        return [
            Section::make('Account Information')
                ->description('Basic user account details')
                ->icon('user')
                ->fields([
                    Text::make('Name')->rules('required'),
                    Email::make('Email')->rules('required', 'email'),
                    Password::make('Password')->requiredOnCreate(),
                ]),

            Section::make('Settings')
                ->collapsible()
                ->collapsed()
                ->fields([
                    Boolean::make('Active')->default(true),
                    Boolean::make('Email Notifications'),
                ]),
        ];
    }
}
```

## Fields

### Conditional Fields

```php
// Show field based on another field's value
Select::make('Type')
    ->options(['individual' => 'Individual', 'company' => 'Company']),

Text::make('Company Name')
    ->showWhen('type', 'company')
    ->requiredWhen('type', 'company'),

Text::make('Tax ID')
    ->showWhen('type', 'company'),

// Hide field based on condition
Boolean::make('Is Admin'),

Select::make('Department')
    ->hideWhen('is_admin', true),
```

### Select with Server-Side Options

```php
// Basic select
Select::make('Status')
    ->options([
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ]),

// Select with enum
Select::make('Status')
    ->options(PostStatus::class),

// Server-side searchable select
MultiSelectServer::make('Tags')
    ->endpoint('/api/tags/search')
    ->searchable()
    ->minChars(2),
```

### Media Field

```php
// Single image upload
Media::make('Avatar')
    ->single()
    ->collection('avatars')
    ->acceptedTypes(['image/jpeg', 'image/png']),

// Multiple files
Media::make('Documents')
    ->multiple()
    ->collection('documents')
    ->maxFiles(5),
```

## Filters

### Select Filter

```php
// With enum
SelectFilter::make('Status')
    ->options(OrderStatus::class),

// With array
SelectFilter::make('Priority')
    ->options([
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ]),

// With relationship
SelectFilter::make('Category')
    ->options(Category::pluck('name', 'id')),
```

### Boolean Filter

```php
BooleanFilter::make('Active')
    ->trueLabel('Active Only')
    ->falseLabel('Inactive Only'),
```

### Date Range Filter

```php
DateRangeFilter::make('Created')
    ->column('created_at'),

DateRangeFilter::make('Order Date')
    ->column('ordered_at'),
```

## Actions

### Custom Action

```php
<?php

namespace App\Actions;

use SavyApps\LaravelStudio\Resources\Actions\Action;
use Illuminate\Support\Collection;

class ActivateUsersAction extends Action
{
    public static string $label = 'Activate Selected';

    public function __construct()
    {
        $this->confirmable()
             ->confirmText('Are you sure you want to activate these users?')
             ->confirmButton('Activate')
             ->success(); // Green button
    }

    public function handle(Collection $models): array
    {
        $count = 0;

        foreach ($models as $user) {
            if (!$user->active) {
                $user->update(['active' => true]);
                $count++;
            }
        }

        return [
            'message' => "{$count} users activated successfully",
            'count' => $count,
        ];
    }
}
```

### Action with Form Fields

```php
<?php

namespace App\Actions;

use SavyApps\LaravelStudio\Resources\Actions\Action;
use SavyApps\LaravelStudio\Resources\Fields\{Select, Textarea};
use Illuminate\Support\Collection;

class ChangeStatusAction extends Action
{
    public static string $label = 'Change Status';

    public function fields(): array
    {
        return [
            Select::make('Status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->rules('required'),

            Textarea::make('Notes')
                ->placeholder('Optional notes about this change'),
        ];
    }

    public function handle(Collection $models, array $fields): array
    {
        $models->each->update([
            'status' => $fields['status'],
            'status_notes' => $fields['notes'] ?? null,
        ]);

        return [
            'message' => 'Status updated for ' . $models->count() . ' records',
        ];
    }
}
```

## Frontend Examples

### Using Resource Service

```javascript
import { resourceService } from '@/services'

// List with pagination and filters
const response = await resourceService.getIndex('users', {
    page: 1,
    per_page: 25,
    search: 'john',
    filters: { active: true },
    sort: 'name',
    direction: 'asc',
})

// Create
await resourceService.create('users', {
    name: 'John Doe',
    email: 'john@example.com',
})

// Update
await resourceService.update('users', 1, {
    name: 'John Updated',
})

// Delete
await resourceService.delete('users', 1)

// Bulk delete
await resourceService.bulkDelete('users', [1, 2, 3])
```

### Using Composables

```javascript
import { useToast, useDialog, usePermissions } from '@/composables'

// Toast notifications
const { success, error, info, warning } = useToast()
success('User created successfully')
error('Failed to save changes')

// Confirmation dialog
const { confirm, alert } = useDialog()
const confirmed = await confirm('Are you sure you want to delete this user?')
if (confirmed) {
    await deleteUser()
}

// Permission checking
const { can, canAny, canAll } = usePermissions()
if (can('users.create')) {
    // Show create button
}
if (canAny(['users.update', 'users.delete'])) {
    // Show actions menu
}
```

### Permission Guard Component

```vue
<template>
    <PermissionGuard permission="users.create">
        <button @click="createUser">Create User</button>
    </PermissionGuard>

    <PermissionGuard :permissions="['users.update', 'users.delete']" require="any">
        <ActionsMenu :user="user" />
    </PermissionGuard>
</template>
```
