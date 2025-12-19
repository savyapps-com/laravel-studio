# Analysis: Moving Starter Features into Core Package

## Current Architecture

| Location | Purpose | File Count |
|----------|---------|------------|
| `src/` | PHP core framework | 87 files |
| `resources/js/` | Reusable Vue components | 56 components |
| `starters/default/backend/` | Application layer (controllers, models, services) | 68 files |
| `starters/default/frontend/` | Application UI (pages, layouts, auth) | 144 files |

---

## What CAN Be Moved (Recommended)

### ~~1. Core Admin Layout System~~ COMPLETED

~~**Move from:** `starters/default/frontend/js/layouts/`~~
~~**Move to:** `resources/js/components/layouts/`~~

| Component | Status |
|-----------|--------|
| ~~`Sidebar.vue`~~ | Moved |
| ~~`MiniSidebar.vue`~~ | Moved |
| ~~`CompactSidebar.vue`~~ | Moved |
| ~~`Navbar.vue`~~ | Moved |
| ~~`HorizontalNav.vue`~~ | Moved |
| ~~`NavItem.vue`~~ | Moved |
| ~~`UserDropdown.vue`~~ | Moved |
| ~~`DarkModeToggle.vue`~~ | Moved |
| ~~`useSidebar.js`~~ | Moved (composable) |
| ~~`useContextRoutes.js`~~ | Moved (composable) |

**Benefit:** Users get a working admin panel UI out-of-box without installation.

---

### ~~2. Auth Pages (Generic Version)~~ COMPLETED

~~**Move from:** `starters/default/frontend/js/pages/auth/`~~
~~**Move to:** `resources/js/components/auth/`~~

| Component | Status |
|-----------|--------|
| ~~`AuthPage.vue`~~ | Moved (wrapper component) |
| ~~`useAuthForms.js`~~ | Moved (composables for login, register, forgot/reset password) |

**Note:** Generic auth page wrapper and form composables are now in the package. Specific page implementations remain in starters as they are customizable.

---

### ~~3. Error Pages~~ COMPLETED

~~**Move from:** `starters/default/frontend/js/pages/admin/errors/`~~
~~**Move to:** `resources/js/components/errors/`~~

| Component | Status |
|-----------|--------|
| ~~`ErrorPage.vue`~~ | Moved (generic error page wrapper) |

**Note:** Generic ErrorPage component is now in the package. It can be configured via props for 404, 403, 500, etc. Specific error page implementations remain in starters.

---

### ~~4. Profile Components~~ COMPLETED

~~**Move from:** `starters/default/frontend/js/components/profile/`~~
~~**Move to:** `resources/js/components/profile/`~~

| Component | Status |
|-----------|--------|
| ~~`ProfileUpdateForm.vue`~~ | Moved |
| ~~`PasswordChangeForm.vue`~~ | Moved |
| `SessionManagement.vue` | Remains in starters (app-specific)

---

### ~~5. Data Table Component~~ COMPLETED

~~**Move from:** `starters/default/frontend/js/components/tables/`~~
~~**Move to:** `resources/js/components/tables/`~~

| Component | Status |
|-----------|--------|
| ~~`DataTable.vue`~~ | Moved |

---

### ~~6. Additional Common Components~~ COMPLETED

~~**Move from:** `starters/default/frontend/js/components/common/`~~
~~**Move to:** `resources/js/components/common/`~~

| Component | Status |
|-----------|--------|
| ~~`BaseModal.vue`~~ | Moved |
| ~~`ModalDialog.vue`~~ | Moved |
| ~~`DropdownMenu.vue`~~ | Moved |
| ~~`RichTextEditor.vue`~~ | Moved (async loaded) |
| ~~`MonacoEditor.vue`~~ | Moved (async loaded) |
| ~~`DarkModeToggle.vue`~~ | Already in layouts |
| ~~`ImpersonationBanner.vue`~~ | Moved (configurable) |

---

### ~~7. Backend Services (PHP)~~ COMPLETED

~~**Move from:** `starters/default/backend/app/Services/`~~
~~**Move to:** `src/Services/`~~

| Service | Status |
|---------|--------|
| ~~`AuthService.php`~~ | Moved |
| `SettingsService.php` | Remains in starters (depends on app-specific Setting model) |
| ~~`ImpersonationService.php`~~ | Moved |
| ~~`BlurPlaceholderService.php`~~ | Moved (to `src/Services/Media/`) |
| ~~`SecureMediaUrlService.php`~~ | Moved (to `src/Services/Media/`) |

---

### 8. Backend Controllers

**Decision:** Controllers remain in starters due to app-specific dependencies (User model, FormRequests, UserResource).

**Location:** `starters/default/backend/app/Http/Controllers/Api/`

| Controller | Status |
|------------|--------|
| `AuthController.php` | Remains (uses package AuthService, ImpersonationService) |
| `MediaController.php` | Remains (uses package Media services) |
| `SettingsController.php` | Remains (depends on app-specific SettingsService) |
| `ImpersonationController.php` | Remains (uses package ImpersonationService) |

**Note:** Controllers now use package services:
```php
use SavyApps\LaravelStudio\Services\AuthService;
use SavyApps\LaravelStudio\Services\ImpersonationService;
use SavyApps\LaravelStudio\Services\Media\BlurPlaceholderService;
use SavyApps\LaravelStudio\Services\Media\SecureMediaUrlService;
```

---

## What Should STAY in Starters (Application-Specific)

### 1. Example Resources
- `UserResource.php`, `RoleResource.php` - These are examples for users to customize

### 2. Application Models
- `User.php` - Every app customizes this differently
- `Setting.php`, `SettingList.php` - App-specific schema

### 3. Email Templates System
- `EmailTemplate.php`, `EmailTemplateService.php` - Optional feature

### 4. Comments System
- `Comment.php`, `CommentService.php` - Optional feature

### 5. Database Seeders
- `PermissionSeeder.php`, `SettingsSeeder.php` - App-specific data

### 6. Example Pages
- `AdvancedFormDemo.vue`, `PerformanceDemo.vue` - Learning examples

### 7. Theme CSS Files
- `themes/*.css` - Customization starting points

---

## Implementation Strategy

### Phase 1: Move Vue Components (Low Risk)

```
resources/js/
├── components/
│   ├── form/          (existing - 23 components)
│   ├── common/        (existing - 10 components → expand to 17)
│   ├── resource/      (existing - 7 components)
│   ├── cards/         (existing - 6 components)
│   ├── activity/      (existing - 4 components)
│   ├── permissions/   (existing - 2 components)
│   ├── search/        (existing - 2 components)
│   ├── layouts/       (NEW - 12 components)   ← Move from starters
│   ├── auth/          (NEW - 6 components)    ← Move from starters
│   ├── errors/        (NEW - 6 components)    ← Move from starters
│   ├── profile/       (NEW - 3 components)    ← Move from starters
│   └── tables/        (NEW - 1 component)     ← Move from starters
├── stores/            (existing - 5 stores)
├── composables/       (existing - 11 composables)
└── services/          (existing - 10 services)
```

### Phase 2: Move PHP Services (Medium Risk)

```
src/
├── Services/
│   ├── ResourceService.php     (existing)
│   ├── PanelService.php        (existing)
│   ├── AuthorizationService.php(existing)
│   ├── ActivityService.php     (existing)
│   ├── GlobalSearchService.php (existing)
│   ├── CardService.php         (existing)
│   ├── AuthService.php         (NEW)    ← Move from starters
│   ├── SettingsService.php     (NEW)    ← Move from starters
│   ├── ImpersonationService.php(NEW)    ← Move from starters
│   └── Media/
│       ├── BlurPlaceholderService.php  (NEW)
│       └── SecureMediaUrlService.php   (NEW)
└── Http/Controllers/
    ├── ResourceController.php  (existing)
    ├── AuthController.php      (NEW)    ← Move from starters
    ├── MediaController.php     (NEW)    ← Move from starters
    ├── SettingsController.php  (NEW)    ← Move from starters
    └── ImpersonationController.php (NEW)
```

### Phase 3: Create Pre-built Admin Panel Mode

Add a config option in `config/studio.php`:

```php
'admin_panel' => [
    'enabled' => env('STUDIO_ADMIN_PANEL', true),
    'layout' => 'classic', // classic, mini, compact, horizontal
    'auth_pages' => true,
    'error_pages' => true,
],
```

---

## Benefits of Moving

| Benefit | Impact |
|---------|--------|
| Zero-install admin panel | Users get working UI immediately |
| Smaller starter pack | Only app-specific files need copying |
| Easier updates | Core UI updates via composer |
| Better DX | `studio:install` becomes optional |
| Consistent UX | All Laravel Studio apps share base UI |

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Breaking existing installs | Provide migration guide |
| Less customization | Use slots, props, CSS variables |
| Larger package size | Tree-shaking, code splitting |
| Version conflicts | Semver, clear changelog |

---

## Summary

**Recommended to Move (40+ components):**
- Layouts (12 components)
- Auth pages (6 components)
- Error pages (6 components)
- Profile components (3 components)
- DataTable (1 component)
- Common components (7 more)
- Backend services (5 services)
- Backend controllers (4 controllers)

**Keep in Starters:**
- Example resources
- Application models
- Database seeders
- Optional features (email templates, comments)
- Theme customization files

This would transform Laravel Studio from a "framework + starter kit" to a "complete admin panel package with optional starter kit for customization."

---

## Implementation Progress

### Completed

| Category | Components Moved | Files Created |
|----------|-----------------|---------------|
| **Layout Components** | 8 components | `resources/js/components/layouts/` |
| | Sidebar, MiniSidebar, CompactSidebar | |
| | Navbar, HorizontalNav, NavItem | |
| | UserDropdown, DarkModeToggle | |
| **Composables** | 4 composables | `resources/js/composables/` |
| | useSidebar, useEscapeKey | |
| | useContextRoutes | |
| | useAuthForms (6 form composables) | |
| **Auth Components** | 1 wrapper | `resources/js/components/auth/` |
| | AuthPage | |
| **Error Components** | 1 wrapper | `resources/js/components/errors/` |
| | ErrorPage | |
| **Profile Components** | 2 forms | `resources/js/components/profile/` |
| | ProfileUpdateForm, PasswordChangeForm | |
| **Table Components** | 1 component | `resources/js/components/tables/` |
| | DataTable | |
| **Common Components** | 6 components | `resources/js/components/common/` |
| | BaseModal, ModalDialog, DropdownMenu | |
| | RichTextEditor (async), MonacoEditor (async) | |
| | ImpersonationBanner | |
| **Backend Services** | 4 services | `src/Services/` |
| | AuthService, ImpersonationService | |
| | BlurPlaceholderService, SecureMediaUrlService | `src/Services/Media/` |

### New Package Exports

Users can now import these components directly from the package:

```javascript
// Layout components
import {
  Sidebar, MiniSidebar, CompactSidebar,
  Navbar, HorizontalNav, NavItem,
  UserDropdown, DarkModeToggle
} from 'laravel-studio'

// Auth components
import { AuthPage } from 'laravel-studio'

// Error components
import { ErrorPage } from 'laravel-studio'

// Profile components
import { ProfileUpdateForm, PasswordChangeForm } from 'laravel-studio'

// Table components
import { DataTable } from 'laravel-studio'

// Common components
import {
  BaseModal, ModalDialog, DropdownMenu,
  ImpersonationBanner,
  RichTextEditor, MonacoEditor  // async loaded
} from 'laravel-studio'

// Composables
import {
  useSidebar, useEscapeKey,
  useContextRoutes,
  useLoginForm, useRegisterForm,
  useForgotPasswordForm, useResetPasswordForm,
  useChangePasswordForm
} from 'laravel-studio'
```

### PHP Services Available

```php
use SavyApps\LaravelStudio\Services\AuthService;
use SavyApps\LaravelStudio\Services\ImpersonationService;
use SavyApps\LaravelStudio\Services\Media\BlurPlaceholderService;
use SavyApps\LaravelStudio\Services\Media\SecureMediaUrlService;
```

### Remaining Work

- [x] Move DataTable component
- [x] Move additional common components (BaseModal, DropdownMenu, etc.)
- [x] Move backend PHP services
- [x] Backend controllers updated to use package services
- [x] Update `studio:install` to note which components are now in package
