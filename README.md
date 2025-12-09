# Laravel Studio

[![Latest Version on Packagist](https://img.shields.io/packagist/v/savyapps-com/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps-com/laravel-studio)
[![Total Downloads](https://img.shields.io/packagist/dt/savyapps-com/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps-com/laravel-studio)
[![License](https://img.shields.io/packagist/l/savyapps-com/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps-com/laravel-studio)

> **A free, open-source Laravel Nova alternative** - craft beautiful admin panels with a fluent resource-based CRUD system, powered by Vue 3.

## Features

- **18 Field Types** - Text, Email, Password, Number, Boolean, Date, Select, Textarea, JSON, Image, Media, ID, BelongsTo, BelongsToMany, HasMany, Group, Section, and more
- **5 Filter Types** - SelectFilter, BooleanFilter, DateRangeFilter, BelongsToManyFilter with enum support
- **4 Built-in Actions** - BulkDelete, BulkUpdate, Export, and Custom actions
- **53 Vue Components** - Complete UI components for forms, tables, cards, modals, and more
- **Auto-generated REST API** - Full CRUD endpoints with search, filters, and bulk operations
- **Permission System** - Role-based authorization with caching and Laravel Gates integration
- **Activity Logging** - Audit trail with change tracking, IP logging, and cleanup
- **Global Search** - Cross-resource search with keyboard shortcuts (Cmd/Ctrl + K)
- **Dashboard Cards** - Value, Trend, Chart, Partition, and Table cards with caching
- **Panel Management** - Multi-panel support with role-based access
- **Media Library** - Spatie Media Library integration with image editing
- **Modern Stack** - Laravel 12, Vue 3, Tailwind CSS 4, Pinia

## Why Laravel Studio?

- **Free & Open Source** - Unlike Laravel Nova ($199-$799 per site)
- **Modern Stack** - Built with the latest Laravel 12, Vue 3, and Tailwind CSS 4
- **Production Ready** - Battle-tested architecture from real projects
- **Developer Friendly** - Clean, intuitive API that feels like Laravel
- **Full Stack** - Backend + frontend in one package

## Requirements

- PHP 8.2+
- Laravel 12.x
- Vue 3.5+
- Node.js 18+

## Installation

**Quick Install (Recommended):**

```bash
# 1. Install the package
composer require savyapps-com/laravel-studio --dev

# 2. IMPORTANT: Regenerate autoload files
composer dump-autoload

# 3. Run the installer (includes all starter files)
php artisan studio:install --all
```

> **Important:** Always run `composer dump-autoload` after installing to avoid "Trait not found" errors.

**See [INSTALLATION.md](INSTALLATION.md) for:**
- Detailed installation steps
- Troubleshooting guide
- Manual installation instructions
- Common error solutions

## Quick Start

### 1. Create a Resource

```bash
php artisan studio:make-resource UserResource
```

### 2. Define Your Resource

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, Email, Password, Boolean, BelongsTo, Section};
use App\Models\User;

class UserResource extends Resource
{
    public static string $model = User::class;
    public static string $title = 'name';
    public static array $search = ['name', 'email'];

    public function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable()->searchable(),
            Email::make('Email')->sortable()->searchable(),
            BelongsTo::make('Role')->searchable(),
            Boolean::make('Active')->toggleable(),
        ];
    }

    public function formFields(): array
    {
        return [
            Section::make('Basic Information')
                ->description('User account details')
                ->icon('user')
                ->fields([
                    Text::make('Name')
                        ->rules('required', 'max:255')
                        ->placeholder('Enter full name'),
                    Email::make('Email')
                        ->rules('required', 'email', 'unique:users,email')
                        ->placeholder('user@example.com'),
                    Password::make('Password')
                        ->requiredOnCreate()
                        ->creationRules('min:8'),
                ]),

            Section::make('Permissions')
                ->collapsible()
                ->fields([
                    BelongsTo::make('Role')
                        ->rules('required')
                        ->searchable(),
                    Boolean::make('Active')
                        ->default(true)
                        ->help('Inactive users cannot login'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Role')->options(Role::pluck('name', 'id')),
            BooleanFilter::make('Active'),
        ];
    }

    public function actions(): array
    {
        return [
            BulkDeleteAction::make(),
            BulkUpdateAction::make()->fields(['active']),
            ExportAction::make()->formats(['csv', 'xlsx']),
        ];
    }
}
```

### 3. Register the Resource

In `config/studio.php`:

```php
'resources' => [
    'users' => \App\Resources\UserResource::class,
    'roles' => \App\Resources\RoleResource::class,
],
```

### 4. Use in Vue

```vue
<template>
    <ResourceManager resource="users" />
</template>

<script setup>
import { ResourceManager } from 'laravel-studio'
</script>
```

That's it! You now have a fully functional CRUD interface with:
- List view with search, filters, sorting, pagination
- Create/Edit forms with validation
- Delete with confirmation
- Bulk operations
- Relationship handling

## Field Types

| Field | Description | Key Methods |
|-------|-------------|-------------|
| `ID` | Auto-incrementing ID | Hidden from forms |
| `Text` | Text input | `placeholder()`, `maxLength()`, `minLength()` |
| `Email` | Email input with validation | `placeholder()` |
| `Password` | Secure password field | `requiredOnCreate()`, `requiredOnUpdate()` |
| `Number` | Numeric input | `min()`, `max()`, `step()` |
| `Boolean` | Toggle/checkbox | `trueLabel()`, `falseLabel()`, `toggleable()` |
| `Date` | Date picker | `format()`, `min()`, `max()` |
| `Select` | Dropdown select | `options()`, `multiple()`, `serverSide()`, `searchable()` |
| `Textarea` | Multi-line text | `rows()`, `maxLength()` |
| `JSON` | JSON editor | `expectedType()`, `autoFormat()`, `showPreview()` |
| `Image` | Image display | `displayType()`, `width()`, `height()`, `rounded()` |
| `Media` | File uploads | `single()`, `multiple()`, `collection()`, `acceptedTypes()` |
| `BelongsTo` | Belongs-to relationship | `resource()`, `titleAttribute()`, `searchable()` |
| `BelongsToMany` | Many-to-many relationship | `resource()`, `titleAttribute()` |
| `HasMany` | Has-many relationship | `resource()` |
| `Group` | Field grouping | `cols()`, `gap()` |
| `Section` | Collapsible sections | `description()`, `icon()`, `collapsible()`, `collapsed()` |

### Common Field Methods

All fields support these methods:

```php
Text::make('Name')
    ->sortable()              // Enable column sorting
    ->searchable()            // Include in search
    ->rules('required')       // Validation rules
    ->default('value')        // Default value
    ->nullable()              // Allow null
    ->placeholder('text')     // Placeholder text
    ->help('Help text')       // Help text below field
    ->cols(6)                 // Grid column span (1-12)
    ->creatable(false)        // Hide on create form
    ->dependsOn('field')      // Conditional visibility
    ->showWhen('field', 'value')
    ->hideWhen('field', 'value')
    ->requiredWhen('field', 'value')
    ->disabledWhen('field', 'value');
```

## Filter Types

| Filter | Description | Methods |
|--------|-------------|---------|
| `SelectFilter` | Dropdown filter | `options()`, `column()` |
| `BooleanFilter` | True/false filter | `column()`, `trueLabel()`, `falseLabel()` |
| `DateRangeFilter` | Date range filter | `column()` |
| `BelongsToManyFilter` | Relationship filter | `options()`, `relationship()` |

### Filter Examples

```php
use SavyApps\LaravelStudio\Resources\Filters\{SelectFilter, BooleanFilter, DateRangeFilter};

public function filters(): array
{
    return [
        // Enum-based options
        SelectFilter::make('Status')
            ->options(UserStatus::class),

        // Array options
        SelectFilter::make('Role')
            ->options([
                'admin' => 'Administrator',
                'user' => 'Regular User',
            ]),

        // Boolean filter
        BooleanFilter::make('Active')
            ->trueLabel('Active Users')
            ->falseLabel('Inactive Users'),

        // Date range
        DateRangeFilter::make('Created At')
            ->column('created_at'),
    ];
}
```

## Actions

| Action | Description | Methods |
|--------|-------------|---------|
| `BulkDeleteAction` | Delete multiple records | `confirmable()`, `danger()` |
| `BulkUpdateAction` | Update multiple records | `fields()` |
| `ExportAction` | Export to CSV/XLSX | `formats()` |

### Custom Actions

```php
use SavyApps\LaravelStudio\Resources\Actions\Action;

class ActivateUsersAction extends Action
{
    public static string $label = 'Activate Selected';

    public function __construct()
    {
        $this->confirmable()
             ->success(); // Green button style
    }

    public function handle(Collection $models): array
    {
        $models->each->update(['active' => true]);

        return [
            'message' => 'Users activated successfully',
            'count' => $models->count(),
        ];
    }
}
```

## Authorization

Laravel Studio includes a powerful authorization system:

```php
use SavyApps\LaravelStudio\Traits\Authorizable;

class UserResource extends Resource
{
    use Authorizable;

    // Define permission group
    public static function permissionGroup(): string
    {
        return 'users';
    }

    // Define available permissions
    public static function permissions(): array
    {
        return [
            'viewAny' => 'View user list',
            'view' => 'View user details',
            'create' => 'Create users',
            'update' => 'Update users',
            'delete' => 'Delete users',
            'bulkDelete' => 'Bulk delete users',
        ];
    }
}
```

In your User model:

```php
use SavyApps\LaravelStudio\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }
}
```

## Activity Logging

Track all changes with built-in activity logging:

```php
use SavyApps\LaravelStudio\Traits\LogsActivity;

class User extends Model
{
    use LogsActivity;

    // Customize logged attributes
    protected static function getLoggedAttributes(): array
    {
        return ['name', 'email', 'active'];
    }

    // Exclude sensitive data
    protected static function getIgnoreAttributeNames(): array
    {
        return ['password', 'remember_token'];
    }
}
```

Configure in `config/studio.php`:

```php
'activity_log' => [
    'enabled' => true,
    'cleanup_days' => 90,
    'log_ip' => true,
    'log_user_agent' => true,
    'default_events' => ['created', 'updated', 'deleted'],
],
```

## Global Search

Enable cross-resource search:

```php
use SavyApps\LaravelStudio\Traits\GloballySearchable;

class UserResource extends Resource
{
    use GloballySearchable;

    public static function globallySearchable(): bool
    {
        return true;
    }

    public static function globallySearchableColumns(): array
    {
        return ['name', 'email'];
    }

    public static function globalSearchTitleColumn(): string
    {
        return 'name';
    }

    public static function globalSearchSubtitleColumn(): string
    {
        return 'email';
    }
}
```

Configure keyboard shortcut in `config/studio.php`:

```php
'global_search' => [
    'enabled' => true,
    'shortcut' => 'cmd+k', // or 'ctrl+k'
    'min_characters' => 2,
    'max_results' => 20,
],
```

## Dashboard Cards

Create dashboard widgets:

```php
use SavyApps\LaravelStudio\Cards\ValueCard;
use SavyApps\LaravelStudio\Cards\TrendCard;

class UserResource extends Resource
{
    public function cards(): array
    {
        return [
            ValueCard::make('Total Users')
                ->value(User::count())
                ->icon('users'),

            TrendCard::make('New Users')
                ->value(User::whereMonth('created_at', now()->month)->count())
                ->previousValue(User::whereMonth('created_at', now()->subMonth()->month)->count())
                ->format('number'),
        ];
    }
}
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan studio:install` | Install Laravel Studio |
| `php artisan studio:make-resource {name}` | Create a new resource |
| `php artisan studio:make-panel` | Create a new panel interactively |
| `php artisan studio:make-action {name}` | Create a custom action |
| `php artisan studio:make-filter {name}` | Create a custom filter |
| `php artisan studio:sync-permissions` | Sync permissions to database |
| `php artisan studio:cleanup-activities` | Clean old activity logs |

### Install Options

```bash
# Full installation with all features
php artisan studio:install --all

# Minimal installation
php artisan studio:install --minimal

# Skip specific steps
php artisan studio:install --skip-migrations --skip-seeders --skip-npm

# Dry run to preview changes
php artisan studio:install --dry-run
```

### Panel Creation

Create admin panels interactively with `studio:make-panel`:

```bash
# Interactive mode (recommended) - prompts for all options
php artisan studio:make-panel

# With panel key argument
php artisan studio:make-panel manager

# Non-interactive with options
php artisan studio:make-panel support \
    --label="Support Panel" \
    --path="/support" \
    --icon="inbox" \
    --role="support" \
    --default \
    -n

# Overwrite existing panel
php artisan studio:make-panel admin --force
```

**Available Options:**

| Option | Description |
|--------|-------------|
| `key` | Panel key/slug (argument) |
| `--label` | Panel display name |
| `--path` | Frontend URL path (e.g., `/admin`) |
| `--icon` | Panel icon (25 icons available) |
| `--role` | Required role for access |
| `--default` | Set as default panel |
| `--inactive` | Create as inactive |
| `--force` | Overwrite existing panel |

**Interactive Features:**
- Select from registered resources to include
- Select from available features (Email Templates, System Settings, etc.)
- Choose from 25 built-in icons
- Auto-generates menu structure
- Shows summary before creating

## API Endpoints

Laravel Studio auto-generates RESTful endpoints for each resource:

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/panels/{panel}/resources/{resource}/meta` | Resource metadata |
| `GET` | `/api/panels/{panel}/resources/{resource}` | List resources |
| `POST` | `/api/panels/{panel}/resources/{resource}` | Create resource |
| `GET` | `/api/panels/{panel}/resources/{resource}/{id}` | Get resource |
| `PUT` | `/api/panels/{panel}/resources/{resource}/{id}` | Update resource |
| `PATCH` | `/api/panels/{panel}/resources/{resource}/{id}` | Partial update |
| `DELETE` | `/api/panels/{panel}/resources/{resource}/{id}` | Delete resource |
| `POST` | `/api/panels/{panel}/resources/{resource}/bulk/delete` | Bulk delete |
| `POST` | `/api/panels/{panel}/resources/{resource}/bulk/update` | Bulk update |
| `POST` | `/api/panels/{panel}/resources/{resource}/actions/{action}` | Run action |
| `GET` | `/api/panels/{panel}/resources/{resource}/search` | Search related |

Additional API endpoints:

- **Panels**: `/api/panels/*` - Panel management
- **Permissions**: `/api/permissions/*` - Permission management
- **Activities**: `/api/activities/*` - Activity log
- **Search**: `/api/search/*` - Global search
- **Cards**: `/api/cards/*` - Dashboard cards

## Vue Components

Laravel Studio provides 53 ready-to-use Vue components:

### Resource Components
- `ResourceManager` - Main CRUD interface
- `ResourceTable` - Data table with sorting/filtering
- `ResourceForm` - Create/edit form
- `FieldRenderer` - Dynamic field rendering
- `FilterBar` - Filter controls
- `ActionButtons` - Bulk action buttons

### Form Components
- `FormInput`, `TextareaInput`, `PasswordInput`
- `SelectInput`, `VirtualSelectInput`, `ServerSelectInput`
- `DateInput`, `CheckboxInput`, `RadioGroup`
- `FileInput`, `MediaUpload`, `JsonEditor`
- `FormSection`, `FormGroup`, `FormLabel`

### Card Components
- `ValueCard`, `TrendCard`, `ChartCard`
- `PartitionCard`, `TableCard`, `CardGrid`

### Common Components
- `ConfirmDialog`, `Toast`, `ToggleSwitch`
- `ImageLightbox`, `ImageEditor`
- `SearchPalette`, `VirtualScroll`

### Permission Components
- `PermissionGuard` - Conditional rendering
- `RolePermissionMatrix` - Role management

## Vue Services

```javascript
import {
    resourceService,
    panelService,
    activityService,
    searchService,
    permissionService,
    cardService
} from 'laravel-studio'

// Resource operations
await resourceService.getIndex('users', { page: 1, search: 'john' })
await resourceService.create('users', formData)
await resourceService.update('users', id, formData)
await resourceService.delete('users', id)
await resourceService.bulkDelete('users', ids)

// Global search
await searchService.globalSearch('query')

// Permissions
const canCreate = await permissionService.checkPermission('users.create')
```

## Vue Composables

```javascript
import {
    useToast,
    useDialog,
    usePermissions,
    useGlobalSearch,
    useLightbox
} from 'laravel-studio'

// Toast notifications
const { success, error, info } = useToast()
success('User created successfully')

// Confirmation dialogs
const { confirm } = useDialog()
if (await confirm('Delete this user?')) {
    // proceed
}

// Permission checking
const { can, canAny } = usePermissions()
if (can('users.create')) {
    // show create button
}
```

## Configuration

All options in `config/studio.php`:

```php
return [
    // Resource registry
    'resources' => [
        'users' => \App\Resources\UserResource::class,
    ],

    // Panel configuration
    'panels' => [
        'admin' => [
            'name' => 'Admin Panel',
            'resources' => ['users', 'roles'],
        ],
    ],

    // Authorization
    'authorization' => [
        'enabled' => true,
        'super_admin_role' => 'super-admin',
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,
        ],
    ],

    // Activity logging
    'activity_log' => [
        'enabled' => true,
        'cleanup_days' => 90,
        'log_ip' => true,
    ],

    // Global search
    'global_search' => [
        'enabled' => true,
        'shortcut' => 'cmd+k',
        'min_characters' => 2,
    ],

    // Dashboard cards
    'cards' => [
        'enabled' => true,
        'cache_ttl' => 300,
        'refresh_interval' => 60,
    ],
];
```

## Roadmap

- [x] Resource-based CRUD system
- [x] 18 field types with conditional visibility
- [x] 5 filter types with enum support
- [x] Bulk actions (delete, update, export)
- [x] Permission and authorization system
- [x] Activity logging with audit trail
- [x] Global search across resources
- [x] Dashboard cards and widgets
- [x] Panel management
- [x] Media library integration
- [ ] Chart integration enhancements
- [ ] Import functionality
- [ ] Real-time notifications
- [ ] API documentation generator

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Credits

- **SavyApps** - [https://savyapps.com](https://savyapps.com)
- Built with love by the SavyApps team

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Alternatives

- [Laravel Nova](https://nova.laravel.com) - Official Laravel admin panel (paid)
- [Filament](https://filamentphp.com) - Beautiful admin panel with Livewire
- [Laravel Backpack](https://backpackforlaravel.com) - Admin panel builder

---

**Laravel Studio** is proudly developed by [SavyApps](https://savyapps.com)
