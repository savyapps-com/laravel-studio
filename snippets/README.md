# VS Code Snippets for Laravel Studio

Code snippets to speed up development with Laravel Studio.

## Installation

### Method 1: Project-level snippets (Recommended)

Copy the snippets file to your project's `.vscode` folder:

```bash
mkdir -p .vscode
cp vendor/savyapps-com/laravel-studio/snippets/laravel-studio.code-snippets .vscode/
```

### Method 2: Global snippets

1. Open VS Code
2. Press `Ctrl+Shift+P` (or `Cmd+Shift+P` on Mac)
3. Type "Configure User Snippets"
4. Select "New Global Snippets file..."
5. Name it `laravel-studio`
6. Copy the contents of `laravel-studio.code-snippets` into the new file

## Available Snippets

### Resources

| Prefix | Description |
|--------|-------------|
| `studio-resource` | Full resource class scaffold |
| `studio-panel-config` | Panel configuration block |
| `studio-resource-config` | Resource configuration block |

### Fields

| Prefix | Description |
|--------|-------------|
| `studio-text` | Text field |
| `studio-number` | Number field |
| `studio-boolean` | Boolean field |
| `studio-select` | Select dropdown field |
| `studio-textarea` | Textarea field |
| `studio-datetime` | DateTime field |
| `studio-media` | Media/file upload field |
| `studio-belongsto` | BelongsTo relationship |
| `studio-hasmany` | HasMany relationship |
| `studio-custom-field` | Custom field class |

### Conditional Visibility

| Prefix | Description |
|--------|-------------|
| `studio-depends` | Basic field dependency |
| `studio-depends-all` | AND logic dependency |
| `studio-depends-any` | OR logic dependency |
| `studio-showwhen` | Callback-based visibility |
| `studio-requiredwhen` | Conditional required |

### Filters & Actions

| Prefix | Description |
|--------|-------------|
| `studio-filter` | Full filter class |
| `studio-action` | Full action class |

### Resource Methods

| Prefix | Description |
|--------|-------------|
| `studio-rules` | Validation rules method |
| `studio-index-query` | Index query customization |
| `studio-with` | Eager loading relations |

## Usage Examples

### Creating a Resource

Type `studio-resource` and press Tab:

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, DateTime};

class ProductResource extends Resource
{
    public static string $model = \App\Models\Product::class;
    public static string $title = 'name';
    public static array $search = ['name'];

    public function fields(): array
    {
        return [
            ID::make(),
            Text::make('Name', 'name')->sortable()->searchable(),
            DateTime::make('Created At', 'created_at')->onlyOnIndex(),
        ];
    }
    // ...
}
```

### Adding Fields

Type `studio-select` and press Tab:

```php
Select::make('Status', 'status')
    ->options([
        'draft' => 'Draft',
        'published' => 'Published',
    ])
    ->rules('required')
```

### Conditional Fields

Type `studio-depends` and press Tab:

```php
->dependsOn('type', 'business')
```

## Tips

- All snippets also work with the `ls-` prefix (e.g., `ls-resource`)
- Use Tab to jump between placeholder values
- Snippets are context-aware and only appear in PHP files
