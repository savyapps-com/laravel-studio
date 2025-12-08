# Laravel Studio

[![Latest Version on Packagist](https://img.shields.io/packagist/v/savyapps/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps/laravel-studio)
[![Total Downloads](https://img.shields.io/packagist/dt/savyapps/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps/laravel-studio)
[![License](https://img.shields.io/packagist/l/savyapps/laravel-studio.svg?style=flat-square)](https://packagist.org/packages/savyapps/laravel-studio)

> **A free, open-source Laravel Nova alternative** - craft beautiful admin panels with a fluent resource-based CRUD system, powered by Vue 3.

## Features

✨ **Resource-based CRUD system** with intuitive fluent API
🎨 **18+ field types** (Text, Select, BelongsTo, Media, Image, and more)
🔍 **5 filter types** for powerful data filtering
⚡ **Bulk actions** (Delete, Update, Export, Custom)
🚀 **Auto-generated API endpoints** for all CRUD operations
💎 **Vue 3 SPA components** that work out of the box
🎯 **Zero-config setup** - define resources, get full CRUD interface
🎭 **Advanced features** - conditional fields, relationships, validation
🌙 **Modern stack** - Laravel 12, Vue 3, Tailwind CSS 4

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
composer require savyapps/laravel-studio --dev

# 2. IMPORTANT: Regenerate autoload files
composer dump-autoload

# 3. Run the installer (includes all starter files)
php artisan studio:install --all
```

> ⚠️ **Important:** Always run `composer dump-autoload` after installing to avoid "Trait not found" errors.

**See [INSTALLATION.md](INSTALLATION.md) for:**
- Detailed installation steps
- Troubleshooting guide
- Manual installation instructions
- Common error solutions

## Quick Start

### 1. Create a Resource

```bash
php artisan studio:make UserResource
```

### 2. Define Your Resource

```php
<?php

namespace App\Resources;

use SavyApps\LaravelStudio\Resources\Resource;
use SavyApps\LaravelStudio\Resources\Fields\{ID, Text, Email, BelongsTo};
use App\Models\User;

class UserResource extends Resource
{
    public static string $model = User::class;

    public function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Name')->sortable()->searchable(),
            Email::make('Email')->sortable()->searchable(),
            BelongsTo::make('Role')->searchable(),
        ];
    }

    public function formFields(): array
    {
        return [
            Text::make('Name')->rules('required', 'max:255'),
            Email::make('Email')->rules('required', 'email', 'unique:users'),
            BelongsTo::make('Role')->rules('required'),
        ];
    }
}
```

### 3. Register the Resource

In `config/studio.php`:

```php
'resources' => [
    'users' => \App\Resources\UserResource::class,
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
- ✅ List view with search, filters, sorting, pagination
- ✅ Create/Edit forms with validation
- ✅ Delete with confirmation
- ✅ Bulk operations
- ✅ Relationship handling

## Documentation

Full documentation is coming soon!

## Roadmap

- [ ] v0.1.0 - Initial release
- [ ] Authorization layer (gates/policies)
- [ ] Resource metrics/cards
- [ ] Global search
- [ ] Import/Export enhancements
- [ ] Widget system
- [ ] Chart integration

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Credits

- **SavyApps** - [https://savyapps.com](https://savyapps.com)
- Built with ❤️ by the SavyApps team

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Alternatives

- [Laravel Nova](https://nova.laravel.com) - Official Laravel admin panel (paid)
- [Filament](https://filamentphp.com) - Beautiful admin panel with Livewire
- [Laravel Backpack](https://backpackforlaravel.com) - Admin panel builder

---

**Laravel Studio** is proudly developed by [SavyApps](https://savyapps.com)
