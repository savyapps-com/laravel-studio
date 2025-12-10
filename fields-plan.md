# Panel Creation Form Modernization Plan

## Current State Analysis

### Database Schema (panels table)
| Column | Type | Notes |
|--------|------|-------|
| key | string | Unique identifier |
| label | string | Display name |
| path | string | URL path prefix |
| icon | string | Icon name |
| role | string | Primary role (legacy) |
| roles | json | Array of allowed roles |
| middleware | json | Array of middleware |
| resources | json | Array of resource keys |
| features | json | Array of feature keys |
| menu | json | Menu structure |
| settings | json | Layout, theme, etc. |
| is_active | boolean | Panel active state |
| is_default | boolean | Default panel flag |
| priority | integer | Display order |

### Current Form Issues
1. **JSON fields are not user-friendly** - Raw JSON input for roles, middleware, resources, features, menu, settings
2. **No icon picker** - Plain text input
3. **Resources/Features** - Should be multi-select from available options (API exists)
4. **Menu builder missing** - Complex nested JSON needs visual builder
5. **Settings not structured** - Should have dedicated fields for layout/theme

---

## Modernization Plan

### Phase 1: Create New Field Types (Package Level)

These will be reusable across any resource in laravel-studio:

#### 1.1 TagInput Field
**Purpose:** Array of strings with tag/chip UI (for roles, middleware)

**Backend:** `packages/laravel-studio/src/Resources/Fields/TagInput.php`
```php
class TagInput extends Field {
    protected function fieldType(): string { return 'tag-input'; }
    public function suggestions(array $suggestions): static
    public function allowCustom(bool $allow = true): static
    public function maxTags(int $max): static
}
```

**Frontend:** `packages/laravel-studio/resources/js/components/form/TagInput.vue`
- Chip-style input with add/remove
- Optional suggestions dropdown
- Keyboard navigation (Enter to add, Backspace to remove)

#### 1.2 IconPicker Field
**Purpose:** Visual icon selection with preview

**Backend:** `packages/laravel-studio/src/Resources/Fields/IconPicker.php`
```php
class IconPicker extends Field {
    protected function fieldType(): string { return 'icon-picker'; }
    public function icons(array $icons): static  // Custom icon set
    public function searchable(bool $searchable = true): static
}
```

**Frontend:** `packages/laravel-studio/resources/js/components/form/IconPicker.vue`
- Grid of available icons
- Search/filter functionality
- Preview of selected icon
- Uses existing Lucide icons from the package

#### 1.3 MultiSelectServer Field
**Purpose:** Multi-select that fetches options from API endpoint

**Backend:** `packages/laravel-studio/src/Resources/Fields/MultiSelectServer.php`
```php
class MultiSelectServer extends Field {
    protected function fieldType(): string { return 'multi-select-server'; }
    public function endpoint(string $url): static
    public function labelKey(string $key): static
    public function valueKey(string $key): static
}
```

**Frontend:** `packages/laravel-studio/resources/js/components/form/MultiSelectServer.vue`
- Fetches options from API on mount
- Checkbox-style multi-select
- Search/filter functionality
- Shows selected items as tags

---

### Phase 2: Update PanelResource Form

**File:** `packages/laravel-studio/starters/default/backend/app/Resources/PanelResource.php`

#### Updated Form Structure:

```php
public function formFields(): array
{
    return [
        // SECTION 1: Basic Information
        Section::make('Basic Information')
            ->icon('layout')
            ->fields([
                Text::make('Key')
                    ->rules('required|string|max:50|alpha_dash')
                    ->placeholder('e.g., admin, vendor, user')
                    ->help('Unique identifier (lowercase, no spaces)')
                    ->cols('col-span-12 md:col-span-6'),

                Text::make('Label')
                    ->rules('required|string|max:100')
                    ->placeholder('e.g., Admin Panel')
                    ->cols('col-span-12 md:col-span-6'),

                Text::make('Path')
                    ->rules('required|string|max:100')
                    ->placeholder('e.g., /admin')
                    ->cols('col-span-12 md:col-span-6'),

                IconPicker::make('Icon')  // NEW
                    ->rules('nullable|string|max:50')
                    ->searchable()
                    ->cols('col-span-12 md:col-span-6'),

                Number::make('Priority')
                    ->rules('nullable|integer|min:0')
                    ->default(100)
                    ->cols('col-span-12 md:col-span-6'),
            ]),

        // SECTION 2: Access Control
        Section::make('Access Control')
            ->icon('shield')
            ->fields([
                TagInput::make('Roles')  // NEW - replaces role + roles
                    ->rules('nullable|array')
                    ->suggestions(['admin', 'user', 'manager', 'editor'])
                    ->allowCustom()
                    ->help('Roles that can access this panel')
                    ->cols('col-span-12 md:col-span-6'),

                TagInput::make('Middleware')  // NEW
                    ->rules('nullable|array')
                    ->suggestions(['auth', 'verified', 'admin', 'api'])
                    ->allowCustom()
                    ->help('Middleware to apply')
                    ->cols('col-span-12 md:col-span-6'),
            ]),

        // SECTION 3: Panel Content
        Section::make('Panel Content')
            ->icon('squares-2x2')
            ->collapsible()
            ->fields([
                MultiSelectServer::make('Resources')  // NEW
                    ->endpoint('/api/admin/panel-management/available-resources')
                    ->labelKey('label')
                    ->valueKey('key')
                    ->help('Resources available in this panel')
                    ->cols('col-span-12 md:col-span-6'),

                MultiSelectServer::make('Features')  // NEW
                    ->endpoint('/api/admin/panel-management/available-features')
                    ->labelKey('label')
                    ->valueKey('key')
                    ->help('Features enabled for this panel')
                    ->cols('col-span-12 md:col-span-6'),

                Json::make('Menu')
                    ->rules('nullable|array')
                    ->help('Menu structure (JSON)')
                    ->rows(10)
                    ->cols('col-span-12'),
            ]),

        // SECTION 4: Appearance & Settings
        Section::make('Appearance & Settings')
            ->icon('palette')
            ->collapsible()
            ->fields([
                Select::make('Layout', 'settings.layout')  // NEW structured
                    ->options([
                        'sidebar' => 'Sidebar (Default)',
                        'topbar' => 'Top Navigation',
                        'minimal' => 'Minimal',
                    ])
                    ->default('sidebar')
                    ->cols('col-span-12 md:col-span-6'),

                Select::make('Theme', 'settings.theme')  // NEW structured
                    ->options([
                        'light' => 'Light',
                        'dark' => 'Dark',
                        'system' => 'System Default',
                    ])
                    ->default('light')
                    ->cols('col-span-12 md:col-span-6'),

                Boolean::make('Active', 'is_active')
                    ->default(true)
                    ->cols('col-span-12 md:col-span-6'),

                Boolean::make('Default', 'is_default')
                    ->default(false)
                    ->cols('col-span-12 md:col-span-6'),
            ]),
    ];
}
```

---

### Phase 3: Update FieldRenderer.vue

**File:** `packages/laravel-studio/resources/js/components/resource/FieldRenderer.vue`

Add rendering logic for new field types:
- `tag-input` → TagInput.vue
- `icon-picker` → IconPicker.vue
- `multi-select-server` → MultiSelectServer.vue

---

### Phase 4: Export New Components

**File:** `packages/laravel-studio/resources/js/index.js`

Export new form components for use in custom implementations.

---

## Files to Create/Modify

### New Files (Package)
1. `packages/laravel-studio/src/Resources/Fields/TagInput.php`
2. `packages/laravel-studio/src/Resources/Fields/IconPicker.php`
3. `packages/laravel-studio/src/Resources/Fields/MultiSelectServer.php`
4. `packages/laravel-studio/resources/js/components/form/TagInput.vue`
5. `packages/laravel-studio/resources/js/components/form/IconPicker.vue`
6. `packages/laravel-studio/resources/js/components/form/MultiSelectServer.vue`

### Modified Files
1. `packages/laravel-studio/resources/js/components/resource/FieldRenderer.vue` - Add new field type rendering
2. `packages/laravel-studio/resources/js/index.js` - Export new components
3. `packages/laravel-studio/starters/default/backend/app/Resources/PanelResource.php` - Update form fields

---

## Future Enhancements (Not in this plan)
- **MenuBuilder** - Drag-and-drop visual menu editor (complex, separate task)
- **ColorPicker** - For custom theme colors
- **PermissionMatrix** - Visual role/permission assignment

---

## Questions for User

1. **Should we keep the legacy `role` field** or migrate fully to `roles` array?
2. **Icon library preference?** (Currently using Lucide icons)
3. **Do you want nested field support** for settings (e.g., `settings.layout`) or keep as flat JSON?
