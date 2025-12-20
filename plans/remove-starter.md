# Plan: Remove Starter Template - Use Everything from Package

## Overview

Refactor Laravel Studio from a "starter template" approach (copying files during install) to a true package-first approach where everything works out-of-the-box with minimal stub generation.

**Goals:**
- Simpler user experience (no file copying, just configure)
- Easier maintenance (no duplicate files to maintain)
- Customization via "publish to override" pattern

---

## Phase 1: Move Files from Starter to Package

### Frontend Files to Move (`starters/default/frontend/js/` → `resources/js/`)

| Source | Destination |
|--------|-------------|
| `layouts/AdminLayout.vue` | `components/layouts/AdminLayout.vue` |
| `layouts/admin/*.vue` | `components/layouts/admin/` |
| `layouts/ProfileLayout.vue` | `components/layouts/ProfileLayout.vue` |
| `layouts/SettingsLayout.vue` | `components/layouts/SettingsLayout.vue` |
| `layouts/DynamicPanelLayout.vue` | `components/layouts/DynamicPanelLayout.vue` |
| `pages/Dashboard.vue` | `pages/Dashboard.vue` |
| `pages/admin/UsersResource.vue` | `pages/resources/UsersResource.vue` |
| `pages/admin/RolesResource.vue` | `pages/resources/RolesResource.vue` |
| `pages/admin/Activity.vue` | `pages/Activity.vue` |
| `pages/admin/EmailTemplates.vue` | `pages/email-templates/` |
| `pages/admin/settings/*` | `pages/settings/` |
| `pages/admin/profile/*` | `pages/profile/` |
| `pages/admin/errors/*` | `pages/errors/` |
| `pages/panel/*` | `pages/panel/` |
| `components/settings/*` | `components/settings/` |
| `config/menuItems.js` | `config/menuItems.js` |
| `css/themes/*` | `css/themes/` |
| `css/app.css` | `css/app.css` |

### Backend Files to Move

| Source | Destination |
|--------|-------------|
| All migrations except `create_users_table` | `database/migrations/` (already partial) |
| `PermissionSeeder.php` | `database/seeders/` |

---

## Phase 2: Create Factory Functions

### 2.1 Create App Factory (`resources/js/bootstrap/app.js`)

```javascript
export function createStudioApp(options = {}) {
  const app = createApp(options.rootComponent || DefaultApp)
  const pinia = createPinia()

  app.use(pinia)
  app.use(options.router || createDefaultRouter())
  app.directive('tooltip', tooltipDirective)

  return app
}
```

### 2.2 Create Router Factory (`resources/js/router/factory.js`)

```javascript
export function createStudioRoutes(options = {}) {
  return [
    ...authRoutes,
    ...createAdminRoutes(options),
    ...createPanelRoutes(options),
    ...errorRoutes,
  ]
}
```

### 2.3 Update Package Exports (`resources/js/index.js`)

Add exports for:
- `createStudioApp`, `createStudioRoutes`
- All layout components
- All page components
- `defaultMenuItems`, `createMenu`

---

## Phase 3: Create Stub Templates

Create stubs in `src/Console/stubs/`:

### Backend Stubs

1. **`user.model.stub`** - Minimal User model with package traits
2. **`routes.api.stub`** - Minimal routes with comment about auto-registration
3. **`routes.web.stub`** - SPA catch-all route
4. **`database.seeder.stub`** - Calls package seeders
5. **`spa.blade.stub`** - Minimal Blade template

### Frontend Stubs

1. **`app.js.stub`** - Entry point using `createStudioApp()`
2. **`router.index.stub`** - Uses `createStudioRoutes()`
3. **`app.css.stub`** - Tailwind imports
4. **`vite.config.stub`** - Vite with package alias
5. **`package.json.stub`** - NPM dependencies

---

## Phase 4: Refactor Install Command

### Files to Modify

- `src/Console/Commands/InstallCommand.php`
- `src/Console/FileMapping.php` (may be deleted/replaced)

### New Install Flow

```
1. Check Prerequisites (PHP, Laravel, packages)
2. Publish config/studio.php
3. Generate Backend Stubs (User, routes, seeder)
4. Generate Frontend Stubs (app.js, router, vite.config, package.json)
5. Run Migrations (package migrations auto-loaded)
6. Run Seeders
7. Install NPM Dependencies (optional)
```

### New Command Options

```bash
php artisan studio:install           # Interactive
php artisan studio:install --all     # Full without prompts
php artisan studio:install --minimal # Backend only
php artisan studio:install --force   # Overwrite existing
php artisan studio:install --dry-run # Preview
```

---

## Phase 5: Update Service Provider

### Add New Publish Tags

```php
// Layouts
$this->publishes([
    __DIR__ . '/../resources/js/components/layouts' => resource_path('js/components/layouts'),
], 'studio-layouts');

// Pages
$this->publishes([
    __DIR__ . '/../resources/js/pages' => resource_path('js/pages'),
], 'studio-pages');

// Themes
$this->publishes([
    __DIR__ . '/../resources/js/css/themes' => resource_path('css/themes'),
], 'studio-themes');

// Seeders
$this->publishes([
    __DIR__ . '/../database/seeders' => database_path('seeders'),
], 'studio-seeders');
```

### Auto-load Package Migrations

```php
$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
```

---

## Phase 6: Delete Starter Directory

After all files are moved and install command is updated:

```bash
rm -rf starters/default/
```

---

## Critical Files to Modify

| File | Changes |
|------|---------|
| `src/Console/Commands/InstallCommand.php` | Replace file copying with stub generation |
| `src/Console/FileMapping.php` | Replace with stub mapping or delete |
| `src/LaravelStudioServiceProvider.php` | Add publish tags, auto-load migrations |
| `resources/js/index.js` | Add new exports (factories, layouts, pages) |
| `resources/js/bootstrap/app.js` | Create app factory (new file) |
| `resources/js/router/factory.js` | Create router factory (new file) |

---

## User Customization Model

### Default Behavior
- Everything works from package
- User only has minimal stubs

### Override via Publishing
```bash
php artisan vendor:publish --tag=studio-layouts   # Override layouts
php artisan vendor:publish --tag=studio-pages     # Override pages
php artisan vendor:publish --tag=studio-themes    # Override themes
```

### Local Override Priority
Vite alias configuration ensures local files take precedence:
```javascript
alias: {
  '@': '/resources/js',                        // Local first
  'laravel-studio': '/vendor/.../resources/js', // Package fallback
}
```

---

## Breaking Changes

1. **Import paths change** - `@/components/...` → `laravel-studio`
2. **Router configuration** - Full router → Router factory
3. **File structure** - Most files move to package
4. **Menu configuration** - Config-driven approach

---

## Implementation Order

1. Move frontend files from starter to package `resources/js/`
2. Create factory functions (app, router)
3. Update `index.js` with new exports
4. Create stub templates
5. Refactor `InstallCommand` to use stubs
6. Update `LaravelStudioServiceProvider` with publish tags
7. Test fresh install
8. Delete `starters/default/` directory
9. Update CLAUDE.md documentation
