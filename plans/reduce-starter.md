# Reduce Starter - Move Components to Core Package

> Analysis of what can be moved from the starter template to the core Laravel Studio package.

---

## Backend Starter Summary

The backend starter is **very lean** - only 4 PHP classes. Most functionality is already delegated to the core package.

| Component | Location | Reusability | Recommendation |
|-----------|----------|-------------|----------------|
| ~~**UserObserver**~~ | ~~`app/Observers/`~~ | ~~90%~~ | ~~**MOVE TO CORE**~~ ✅ |
| ~~**PermissionSeeder**~~ | ~~`database/seeders/`~~ | ~~80%~~ | ~~**MOVE TO CORE**~~ ✅ |
| **User Model** | `app/Models/` | 80% | Keep (has app-specific LLM refs) |
| **AppServiceProvider** | `app/Providers/` | 40% | Keep as template |
| **DatabaseSeeder** | `database/seeders/` | 50% | Keep as example |
| **Migrations** | `database/migrations/` | 95% | Already have fallbacks in core |

### High Priority Backend Moves

1. ~~`UserObserver` → `src/Observers/UserSettingsObserver.php` - handles default user settings initialization~~ ✅
2. ~~`PermissionSeeder` → `src/Database/Seeders/PermissionSeeder.php` - syncs permissions and creates super admin~~ ✅

---

## Frontend Starter Summary

The frontend has ~51 Vue/JS files. About **50% (25 files)** are generic and reusable.

### Should Move to Core (High Priority)

| Category | Files | Description |
|----------|-------|-------------|
| **Layouts** | 8 files | AdminLayout, DynamicPanelLayout, ProfileLayout, SettingsLayout + 4 admin variants |
| **Profile Pages** | 2 files | Profile.vue, ChangePassword.vue |
| **Settings Pages** | 4 files | Settings.vue, Appearance.vue, Notifications.vue, Preferences.vue |
| **Error Pages** | 6 files | 404, 403, 401, 500, Network, Maintenance |
| **Dynamic Pages** | 2 files | DynamicResource.vue, Activity.vue |
| **Composables** | 1 file | useProfileForm.js |
| **Root Component** | 1 file | App.vue |

**Total: ~24 files should move to core**

### Keep in Starter (Examples/App-Specific)

| Category | Files | Reason |
|----------|-------|--------|
| **Menu Config** | menuItems.js | App-specific navigation |
| **Resource Pages** | UsersResource.vue, RolesResource.vue | Examples |
| **Email Templates** | EmailTemplates.vue, EmailTemplateForm.vue | Business-specific |
| **System Settings** | Global.vue, System.vue | Admin-specific |
| **Examples** | AdvancedFormDemo.vue, PerformanceDemo.vue | Demo pages |
| **Guest Animations** | guest.js | Landing page specific |

---

## Easy Wins - Quick Moves

These can be moved with minimal changes:

1. **Error Pages** (6 files) - Completely generic, just move
2. **Profile/ChangePassword Pages** (2 files) - Generic forms using core services
3. **Appearance Settings** (1 file) - Uses core LayoutOption, ThemeSelector
4. **DynamicResource.vue** (1 file) - Just wraps ResourceManager
5. **useProfileForm.js** (1 file) - Generic composable
6. ~~**UserObserver** (backend) - Configurable settings initialization~~ ✅
7. ~~**PermissionSeeder** (backend) - Standard permission sync~~ ✅

---

## Medium Effort - Needs Refactoring

These need some adjustment:

1. **Layout Components** - Need to abstract menu config dependency
2. **DynamicPanelLayout.vue** - Core logic is generic, menu loading needs flexibility
3. **Router Configuration** - Extract generic route patterns, keep menu config separate
4. **ProfileLayout/SettingsLayout** - Tab configuration needs to be flexible

---

## Duplicates to Consolidate

Currently duplicated (admin vs user versions are identical):

- `pages/profile/` and `pages/admin/profile/` - Same components
- `pages/settings/` and `pages/admin/settings/` - Same components

**Solution:** Move to core once, use in both contexts.

---

## Re-exports to Eliminate

These files just re-export from core - can be removed once users import directly:

- `utils/index.js` → import from `@savyapps/laravel-studio`
- `utils/validationSchemas.js` → import from `@savyapps/laravel-studio`
- `composables/useContextRoutes.js` → import from `@savyapps/laravel-studio`
- `theme.config.js` → import from `@savyapps/laravel-studio`
- `components/settings/LayoutOption.vue` → use core directly
- `components/settings/SettingGroup.vue` → use core directly

---

## Recommended Core Package Additions

```
src/ (backend)
├── Observers/
│   └── UserSettingsObserver.php      # NEW - from UserObserver
└── Database/
    └── Seeders/
        └── PermissionSeeder.php      # NEW - from starter

resources/js/ (frontend)
├── layouts/
│   ├── AdminLayout.vue               # NEW
│   ├── DynamicPanelLayout.vue        # NEW
│   ├── ProfileLayout.vue             # NEW
│   ├── SettingsLayout.vue            # NEW
│   └── admin/
│       ├── ClassicLayout.vue         # NEW
│       ├── HorizontalLayout.vue      # NEW
│       ├── CompactLayout.vue         # NEW
│       └── MiniLayout.vue            # NEW
├── pages/
│   ├── profile/
│   │   ├── Profile.vue               # NEW
│   │   └── ChangePassword.vue        # NEW
│   ├── settings/
│   │   ├── Settings.vue              # NEW
│   │   ├── Appearance.vue            # NEW
│   │   ├── Notifications.vue         # NEW
│   │   └── Preferences.vue           # NEW
│   ├── errors/
│   │   ├── NotFound.vue              # NEW
│   │   ├── Forbidden.vue             # NEW
│   │   ├── Unauthorized.vue          # NEW
│   │   ├── ServerError.vue           # NEW
│   │   ├── NetworkError.vue          # NEW
│   │   └── MaintenanceMode.vue       # NEW
│   ├── Activity.vue                  # NEW
│   └── DynamicResource.vue           # NEW
├── composables/
│   └── useProfileForm.js             # NEW
└── App.vue                           # NEW
```

---

## Impact Summary

| Area | Files to Move | Effort | Benefit |
|------|---------------|--------|---------|
| **Backend** | 2 | Low | Standardized permissions & user settings |
| **Layouts** | 8 | Medium | Consistent admin layouts across all apps |
| **Pages** | 13 | Medium | Reusable profile, settings, error pages |
| **Composables** | 1 | Low | Shared form logic |
| **Remove re-exports** | 6 | Low | Cleaner imports |

**Total: ~30 files to move, reducing starter to just examples and config**

---

## Detailed Backend Analysis

### ~~UserObserver (90% Reusable)~~ ✅ COMPLETED

**Location:** `src/Observers/UserSettingsObserver.php`

**What it does:**
- Creates default user settings on user creation
- Sets theme, layout, dark mode, and items per page preferences
- Uses configuration for defaults (`config('studio.user_settings.defaults.*')`)
- Cleans up settings on user deletion

**Implementation:**
- Created as configurable observer in core package
- Setting keys configurable via `config('studio.user_settings.keys.*')`
- Default values configurable via `config('studio.user_settings.defaults.*')`
- Supports additional custom settings via `config('studio.user_settings.additional')`
- Starter now extends core observer for customization

### ~~PermissionSeeder (80% Reusable)~~ ✅ COMPLETED

**Location:** `src/Database/Seeders/PermissionSeeder.php`

**What it does:**
1. Calls `AuthorizationService::syncPermissions()` from core
2. Falls back to manual permission creation from `Permission` enum
3. Assigns permissions to roles based on `Permission` enum methods
4. Creates super admin user with credentials from config

**Implementation:**
- Created in core package with full configuration support
- Super admin creation toggleable via `config('studio.seeder.create_super_admin')`
- Email, name, password configurable via `config('studio.seeder.super_admin_*')`
- Starter now extends core seeder for customization

---

## Detailed Frontend Analysis

### Layout Components (All 8 - 100% Reusable)

| File | Purpose | Dependencies |
|------|---------|--------------|
| `AdminLayout.vue` | Dynamic layout switcher (classic/horizontal/compact/mini) | settingsStore, layout components |
| `DynamicPanelLayout.vue` | Multi-panel layout with menu from API | panelService, settingsStore |
| `ProfileLayout.vue` | Profile tabbed layout | router |
| `SettingsLayout.vue` | Settings tabbed layout | router |
| `ClassicLayout.vue` | Sidebar layout variant | Sidebar, Navbar, Footer |
| `HorizontalLayout.vue` | Top nav layout variant | Sidebar, Navbar, Footer |
| `CompactLayout.vue` | Compact sidebar variant | Sidebar, Navbar, Footer |
| `MiniLayout.vue` | Minimal sidebar variant | Sidebar, Navbar, Footer |

**All use core components (Sidebar, Navbar, Footer) and stores (settingsStore, authStore)**

### Profile Pages (2 Files - 100% Reusable)

| File | Purpose |
|------|---------|
| `Profile.vue` | User profile update form with avatar upload |
| `ChangePassword.vue` | Password change form with validation |

**Both use core services (authService) and composables (useProfileForm, useChangePasswordForm)**

### Settings Pages (4 Files - 95% Reusable)

| File | Purpose |
|------|---------|
| `Settings.vue` | Settings layout wrapper |
| `Appearance.vue` | Theme and layout selector |
| `Notifications.vue` | Notification preferences |
| `Preferences.vue` | User preferences (items per page, etc.) |

**All use core components (LayoutOption, ThemeSelector, SettingGroup)**

### Error Pages (6 Files - 100% Reusable)

| File | HTTP Code | Description |
|------|-----------|-------------|
| `NotFound.vue` | 404 | Page not found |
| `Forbidden.vue` | 403 | Access forbidden |
| `Unauthorized.vue` | 401 | Not authenticated |
| `ServerError.vue` | 500 | Server error |
| `NetworkError.vue` | - | Network connectivity issue |
| `MaintenanceMode.vue` | 503 | System maintenance |

**All are standalone with no external dependencies beyond router**

### Dynamic Pages (2 Files - 100% Reusable)

| File | Purpose |
|------|---------|
| `DynamicResource.vue` | Wraps ResourceManager for dynamic resources |
| `Activity.vue` | Activity log viewer with filtering |

**Both use core components (ResourceManager, DataTable)**

### Composables (1 File - 100% Reusable)

| File | Purpose |
|------|---------|
| `useProfileForm.js` | Profile form with VeeValidate integration |

**Uses core services and validation schemas**

---

## Implementation Order

### ~~Phase 1: Easy Backend Moves~~ ✅ COMPLETED
1. ~~Move `UserObserver` to core as `UserSettingsObserver`~~ ✅
2. ~~Move `PermissionSeeder` to core~~ ✅

### Phase 2: Easy Frontend Moves
1. Move all 6 error pages to core
2. Move `Profile.vue` and `ChangePassword.vue` to core
3. Move `useProfileForm.js` to core
4. Move `DynamicResource.vue` to core

### Phase 3: Layout Components
1. Move all 4 admin layout variants to core
2. Move `AdminLayout.vue` to core
3. Move `ProfileLayout.vue` and `SettingsLayout.vue` to core
4. Move `DynamicPanelLayout.vue` to core (needs menu abstraction)

### Phase 4: Settings Pages
1. Move `Settings.vue`, `Appearance.vue` to core
2. Move `Notifications.vue`, `Preferences.vue` to core
3. Move `Activity.vue` to core

### Phase 5: Cleanup
1. Remove re-export files from starter
2. Update starter imports to use core directly
3. Update documentation

---

## Files to Keep in Starter

After all moves, the starter should only contain:

```
starters/default/
├── backend/
│   ├── app/
│   │   ├── Models/User.php           # App-specific user model
│   │   └── Providers/AppServiceProvider.php  # App bootstrap
│   ├── config/studio.php             # App configuration
│   ├── database/
│   │   ├── seeders/DatabaseSeeder.php  # Example seeder
│   │   └── factories/UserFactory.php   # User factory
│   ├── routes/                       # App routes
│   └── bootstrap/app.php             # Laravel bootstrap
└── frontend/
    ├── js/
    │   ├── config/menuItems.js       # App menu config
    │   ├── pages/
    │   │   ├── Dashboard.vue         # App dashboard
    │   │   ├── admin/
    │   │   │   ├── UsersResource.vue   # Example resource
    │   │   │   ├── RolesResource.vue   # Example resource
    │   │   │   ├── EmailTemplates.vue  # Example feature
    │   │   │   └── EmailTemplateForm.vue
    │   │   └── settings/
    │   │       ├── Global.vue        # App-specific settings
    │   │       └── System.vue        # App-specific settings
    │   ├── router/index.js           # App routes (using core patterns)
    │   ├── guest.js                  # Landing page animations
    │   └── spa.js                    # SPA initialization
    └── css/                          # App styles
```

This reduces the starter from ~55 files to ~15 files, making it purely about app-specific configuration and examples.
