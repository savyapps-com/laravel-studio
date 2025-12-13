# Laravel Studio Package Consolidation & Modernization Plan

---

## PART 1: Starter Pack Consolidation (Remove Duplicates)

### Goal
Remove ~97 duplicated files from the starter pack and update imports to use the package directly.

### Phase 1.1: Remove Duplicated Form Components (20 files)

**Files to DELETE from starter:**
```
starters/default/frontend/js/components/form/
├── CheckboxInput.vue      → import from 'laravel-studio'
├── DateInput.vue          → import from 'laravel-studio'
├── FileInput.vue          → import from 'laravel-studio'
├── FormActions.vue        → import from 'laravel-studio'
├── FormError.vue          → import from 'laravel-studio'
├── FormGroup.vue          → import from 'laravel-studio'
├── FormHelpText.vue       → import from 'laravel-studio'
├── FormInput.vue          → import from 'laravel-studio'
├── FormLabel.vue          → import from 'laravel-studio'
├── FormSection.vue        → import from 'laravel-studio'
├── FormSuccess.vue        → import from 'laravel-studio'
├── JsonEditor.vue         → import from 'laravel-studio'
├── MediaUpload.vue        → import from 'laravel-studio'
├── PasswordInput.vue      → import from 'laravel-studio'
├── RadioGroup.vue         → import from 'laravel-studio'
├── ResourceSelectInput.vue→ import from 'laravel-studio'
├── SelectInput.vue        → import from 'laravel-studio'
├── ServerSelectInput.vue  → import from 'laravel-studio'
├── TextareaInput.vue      → import from 'laravel-studio'
└── VirtualSelectInput.vue → import from 'laravel-studio'
```

### Phase 1.2: Remove Duplicated Stores (4 files)

**Files to DELETE from starter:**
```
starters/default/frontend/js/stores/
├── auth.js      → import { useAuthStore } from 'laravel-studio'
├── dialog.js    → import { useDialogStore } from 'laravel-studio'
├── settings.js  → import { useSettingsStore } from 'laravel-studio'
└── toast.js     → import { useToastStore } from 'laravel-studio'
```

### Phase 1.3: Remove Duplicated Utilities (9 files)

**Files to DELETE from starter:**
```
starters/default/frontend/js/utils/
├── validationRules.js      → import from 'laravel-studio'
├── validationMessages.js   → import from 'laravel-studio'
├── validationSchemas.js    → import from 'laravel-studio'
├── laravelErrorMapper.js   → import from 'laravel-studio'
├── httpErrorHandler.js     → import from 'laravel-studio'
├── imageManipulation.js    → import from 'laravel-studio'
├── memoization.js          → import from 'laravel-studio'
├── debouncedValidation.js  → import from 'laravel-studio'
└── lazyValidation.js       → import from 'laravel-studio'
```

### Phase 1.4: Remove Duplicated Directive (1 file)

**File to DELETE from starter:**
```
starters/default/frontend/js/directives/tooltip.js → import { tooltipDirective } from 'laravel-studio'
```

---

### Files to Modify Summary

| Action | Location | Count |
|--------|----------|-------|
| DELETE | `starters/default/frontend/js/components/form/` | 20 files |
| DELETE | `starters/default/frontend/js/stores/` | 4 files |
| DELETE | `starters/default/frontend/js/utils/` | 9 files |
| DELETE | `starters/default/frontend/js/directives/tooltip.js` | 1 file |
| MODIFY | Various starter files (update imports) | ~15 files |

**Total Reduction: 34 files deleted, ~54% frontend reduction**

---

## PART 2: New Field Types for Panel Form Modernization

### New Field Types to Create

#### 1. TagInput Field
**Purpose:** Array of strings with tag/chip UI (for roles, middleware)

**Backend:** `src/Resources/Fields/TagInput.php`
**Frontend:** `resources/js/components/form/TagInput.vue`

#### 2. IconPicker Field
**Purpose:** Visual icon selection with preview

**Backend:** `src/Resources/Fields/IconPicker.php`
**Frontend:** `resources/js/components/form/IconPicker.vue`

#### 3. MultiSelectServer Field
**Purpose:** Multi-select that fetches options from API endpoint

**Backend:** `src/Resources/Fields/MultiSelectServer.php`
**Frontend:** `resources/js/components/form/MultiSelectServer.vue`

---

## Files to Create/Modify

### New Files
1. `src/Resources/Fields/TagInput.php`
2. `src/Resources/Fields/IconPicker.php`
3. `src/Resources/Fields/MultiSelectServer.php`
4. `resources/js/components/form/TagInput.vue`
5. `resources/js/components/form/IconPicker.vue`
6. `resources/js/components/form/MultiSelectServer.vue`

### Modified Files
1. `resources/js/components/resource/FieldRenderer.vue` - Add new field type rendering
2. `resources/js/index.js` - Export new components
3. `starters/default/backend/app/Resources/PanelResource.php` - Update form fields

---

## User Decisions

1. **Legacy `role` field** → Migrate fully to `roles` array (remove `role` field from form)
2. **Icon library** → Keep current custom inline SVG icons (145+ icons in Icon.vue)
3. **Settings field** → Keep as flat JSON for now (no nested field support)
