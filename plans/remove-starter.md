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

## Implementation Order

1. Move frontend files from starter to package `resources/js/`
2. **Refactor all package imports to use relative paths** (Critical)
3. Create factory functions (app, router)
4. **Create TypeScript declarations** (`types/*.d.ts`)
5. Update `index.js` with new exports
6. Create stub templates (including IDE config stubs)
7. **Create Vite plugin for override resolution**
8. Refactor `InstallCommand` to use stubs
9. Update `LaravelStudioServiceProvider` with publish tags
10. **Write override resolution tests**
11. Test fresh install
12. Delete `starters/default/` directory
13. Update CLAUDE.md documentation

---

## Import Path Strategy (Critical)

### Problem
Package internal code must not use `@/` aliases - this would break when users override files.

### Solution: Relative Imports in Package

**All package code uses relative imports:**
```javascript
// CORRECT - Package internal (resources/js/components/form/TextField.vue)
import FormGroup from './FormGroup.vue'
import { useField } from '../../composables/useField'

// WRONG - Would break with user overrides
import FormGroup from '@/components/form/FormGroup.vue'
```

### User Code Uses Aliases

**User app.js:**
```javascript
// Import from package
import { TextField } from 'laravel-studio/components'

// Import local override (if exists)
import CustomField from '@/components/CustomField.vue'
```

### Vite Configuration

```javascript
// vite.config.js (generated stub)
import { fileURLToPath } from 'url'
import { existsSync } from 'fs'

function studioResolver() {
  return {
    name: 'studio-resolver',
    resolveId(source, importer) {
      // Check for local override first
      if (source.startsWith('laravel-studio/')) {
        const localPath = source.replace(
          'laravel-studio/',
          './resources/js/'
        )
        if (existsSync(localPath)) {
          return localPath
        }
      }
      return null // Fall through to package
    }
  }
}

export default defineConfig({
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      'laravel-studio': fileURLToPath(
        new URL('./vendor/savyapps/laravel-studio/resources/js', import.meta.url)
      ),
    },
  },
  plugins: [studioResolver()],
})
```

### Package Export Structure

```javascript
// Package resources/js/index.js
// Named exports for tree-shaking
export { TextField, SelectField, ... } from './components/form'
export { AdminLayout, ProfileLayout, ... } from './components/layouts'
export { useToast, useDialog, ... } from './composables'
export { resourceService, authService, ... } from './services'
export { createStudioApp } from './bootstrap/app'
export { createStudioRouter, createStudioRoutes } from './router/factory'
```

### Import Patterns for Users

```javascript
// Recommended: Named imports (tree-shakeable)
import { TextField, SelectField } from 'laravel-studio/components'
import { useToast } from 'laravel-studio/composables'

// Also works: Direct file import
import TextField from 'laravel-studio/components/form/TextField.vue'
```

---

## TypeScript Support

### Type Definitions Location
```
resources/js/
├── types/
│   ├── index.d.ts          # Main type exports
│   ├── components.d.ts     # Component prop types
│   ├── services.d.ts       # Service return types
│   ├── composables.d.ts    # Composable return types
│   └── resources.d.ts      # Resource/Field types
└── index.d.ts              # Root declaration file
```

### Package.json Exports (for TypeScript)
```json
{
  "name": "laravel-studio",
  "types": "./resources/js/index.d.ts",
  "exports": {
    ".": {
      "types": "./resources/js/index.d.ts",
      "import": "./resources/js/index.js"
    },
    "./components": {
      "types": "./resources/js/types/components.d.ts",
      "import": "./resources/js/components/index.js"
    },
    "./composables": {
      "types": "./resources/js/types/composables.d.ts",
      "import": "./resources/js/composables/index.js"
    },
    "./services": {
      "types": "./resources/js/types/services.d.ts",
      "import": "./resources/js/services/index.js"
    }
  }
}
```

### Key Type Definitions

```typescript
// resources/js/types/index.d.ts

// App Factory
export interface StudioAppOptions {
  rootComponent?: Component
  router?: Router
  plugins?: Plugin[]
}

export function createStudioApp(options?: StudioAppOptions): App

// Router Factory
export interface StudioRouterOptions {
  additionalRoutes?: RouteRecordRaw[]
  adminRoutes?: RouteRecordRaw[]
  panelRoutes?: RouteRecordRaw[]
  authRoutes?: RouteRecordRaw[]
}

export function createStudioRouter(options?: StudioRouterOptions): Router
export function createStudioRoutes(options?: StudioRouterOptions): RouteRecordRaw[]

// Component Props
export interface TextFieldProps {
  field: Field
  modelValue: string | null
  errors?: Record<string, string[]>
  disabled?: boolean
}

// Service Types
export interface ResourceService {
  getIndex(resource: string, params?: IndexParams): Promise<PaginatedResponse>
  create(resource: string, data: Record<string, any>): Promise<ResourceResponse>
  update(resource: string, id: number, data: Record<string, any>): Promise<ResourceResponse>
  delete(resource: string, id: number): Promise<void>
}
```

### User tsconfig.json Setup

```json
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./resources/js/*"],
      "laravel-studio": ["./vendor/savyapps/laravel-studio/resources/js"],
      "laravel-studio/*": ["./vendor/savyapps/laravel-studio/resources/js/*"]
    }
  }
}
```

---

## IDE Configuration

### VS Code Settings

Create `.vscode/settings.json`:
```json
{
  "typescript.preferences.importModuleSpecifier": "non-relative",
  "javascript.preferences.importModuleSpecifier": "non-relative",
  "path-intellisense.mappings": {
    "@": "${workspaceFolder}/resources/js",
    "laravel-studio": "${workspaceFolder}/vendor/savyapps/laravel-studio/resources/js"
  }
}
```

### jsconfig.json (for non-TS projects)

```json
{
  "compilerOptions": {
    "baseUrl": ".",
    "paths": {
      "@/*": ["resources/js/*"],
      "laravel-studio": ["vendor/savyapps/laravel-studio/resources/js"],
      "laravel-studio/*": ["vendor/savyapps/laravel-studio/resources/js/*"]
    }
  },
  "include": [
    "resources/js/**/*",
    "vendor/savyapps/laravel-studio/resources/js/**/*"
  ]
}
```

### PHPStorm / WebStorm

1. Mark `vendor/savyapps/laravel-studio/resources/js` as "Resource Root"
2. Configure webpack/vite config path in Settings → Languages → JavaScript → Webpack

### Volar (Vue Language Server)

Works automatically with tsconfig.json paths configuration.

### Generated Stub Includes IDE Config

The `studio:install` command will generate:
- `jsconfig.json` or update existing
- `.vscode/settings.json` with path mappings
- `tsconfig.json` paths (if TypeScript detected)

---

## Testing Strategy

### Unit Tests: Override Resolution

```javascript
// tests/js/override-resolution.test.js
import { describe, it, expect, vi } from 'vitest'
import { resolveStudioComponent } from 'laravel-studio/resolver'

describe('Component Override Resolution', () => {
  it('uses local component when it exists', () => {
    // Mock file system
    vi.mock('fs', () => ({
      existsSync: (path) => path.includes('resources/js/components/CustomButton')
    }))

    const resolved = resolveStudioComponent('components/CustomButton.vue')
    expect(resolved).toContain('resources/js/components/CustomButton')
  })

  it('falls back to package when local does not exist', () => {
    vi.mock('fs', () => ({
      existsSync: () => false
    }))

    const resolved = resolveStudioComponent('components/TextField.vue')
    expect(resolved).toContain('vendor/savyapps/laravel-studio')
  })
})
```

### Integration Tests: Published Overrides

```javascript
// tests/js/integration/published-override.test.js
import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeAll } from 'vitest'

describe('Published Component Override', () => {
  beforeAll(() => {
    // Simulate published override
    // Copy custom AdminLayout to resources/js/components/layouts/
  })

  it('uses published AdminLayout over package default', async () => {
    const { AdminLayout } = await import('laravel-studio/layouts')
    const wrapper = mount(AdminLayout)

    // Assert custom content is present
    expect(wrapper.html()).toContain('custom-admin-class')
  })
})
```

### E2E Tests: Full Override Flow

```javascript
// tests/e2e/override-flow.spec.js
import { test, expect } from '@playwright/test'

test('custom dashboard is rendered when published', async ({ page }) => {
  // 1. Fresh install (package dashboard)
  await page.goto('/admin/dashboard')
  await expect(page.locator('[data-testid="package-dashboard"]')).toBeVisible()

  // 2. Publish override
  // (run: php artisan vendor:publish --tag=studio-pages)

  // 3. Verify custom dashboard
  await page.reload()
  await expect(page.locator('[data-testid="custom-dashboard"]')).toBeVisible()
})
```

### CI Pipeline Test Matrix

```yaml
# .github/workflows/test.yml
jobs:
  test-overrides:
    strategy:
      matrix:
        scenario:
          - fresh-install        # No overrides
          - all-overrides        # All files published
          - partial-overrides    # Some files published
          - custom-theme         # Theme override only
    steps:
      - uses: actions/checkout@v4
      - name: Setup scenario
        run: ./scripts/setup-test-scenario.sh ${{ matrix.scenario }}
      - name: Run tests
        run: npm run test
```

### Package Internal Tests

```php
// tests/Feature/OverrideResolutionTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class OverrideResolutionTest extends TestCase
{
    public function test_publish_creates_override_files(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'studio-layouts'])
            ->assertSuccessful();

        $this->assertFileExists(resource_path('js/components/layouts/AdminLayout.vue'));
    }

    public function test_vite_config_stub_has_correct_aliases(): void
    {
        $this->artisan('studio:install', ['--force' => true]);

        $viteConfig = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString("'laravel-studio':", $viteConfig);
        $this->assertStringContainsString("'@':", $viteConfig);
    }
}
```
