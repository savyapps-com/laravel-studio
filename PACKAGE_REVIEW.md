# Laravel Studio - Package Review

**Review Date:** December 17, 2025
**Package Version:** Development
**Reviewer:** Code Analysis
**Overall Score:** 7.5/10

---

## Executive Summary

Laravel Studio is a well-architected open-source Laravel Nova alternative that demonstrates solid engineering fundamentals. The package features a clean service layer, fluent API design, and good security awareness. However, several gaps in security hardening, test coverage, and feature completeness need to be addressed before production deployment with sensitive data.

---

## Table of Contents

1. [Strengths](#strengths)
2. [Critical Issues](#critical-issues)
3. [Missing Features](#missing-features-vs-nova)
4. [Test Coverage Analysis](#test-coverage-analysis)
5. [API Design Issues](#api-design-issues)
6. [Documentation Accuracy](#documentation-accuracy)
7. [Dependency Concerns](#dependency-concerns)
8. [Recommendations](#recommendations)
9. [Score Breakdown](#score-breakdown)

---

## Strengths

### 1. Clean Architecture

- **Service Layer Separation**: Business logic properly separated from controllers via `ResourceService`, `AuthorizationService`, `PanelService`, etc.
- **Trait-based Composition**: Flexible functionality through `Authorizable`, `GloballySearchable`, `LogsActivity`, `HasPermissions`, and `ApiResponse` traits
- **Fluent API Design**: Mirrors Nova's intuitive API pattern

```php
Text::make('Name')
    ->sortable()
    ->searchable()
    ->rules('required', 'max:255')
    ->placeholder('Enter name');
```

### 2. Security Awareness

| Security Feature | Location | Status |
|-----------------|----------|--------|
| SQL Injection Prevention | `ResourceService.php:296-305` | ✅ Whitelist-based |
| Dangerous File Extensions | `Media.php:22-31` | ✅ Blacklist includes PHP, executables, scripts |
| Server-side Field Visibility | `ResourceService.php:456-468` | ✅ Prevents hidden field manipulation |
| Password Auto-hashing | `ResourceService.php:152-155` | ✅ Automatic |
| Authorization Checks | `Authorizable.php` | ✅ Policy-first with permission fallback |

### 3. Performance Optimizations

**In-memory Caching** prevents repeated computation within requests:

```php
// ResourceService.php
protected ?array $cachedIndexFields = null;
protected ?array $cachedFormFields = null;
protected ?array $cachedSortableColumns = null;
protected ?array $cachedRelationships = null;
```

**Automatic Eager Loading** of BelongsTo relationships prevents N+1 queries.

### 4. Type-safe Permissions

The `Permission` enum provides compile-time safety:

```php
use SavyApps\LaravelStudio\Enums\Permission;

Permission::USERS_CREATE;  // 'users.create' - prevents typos
Permission::isValid('users.create');  // Runtime validation
```

---

## Critical Issues

### 1. `bcrypt()` is Deprecated

**Location:** `ResourceService.php:154, 181`

```php
// Current (legacy)
$modelData['password'] = bcrypt($modelData['password']);

// Recommended
$modelData['password'] = Hash::make($modelData['password']);
```

**Impact:** `bcrypt()` doesn't respect `config/hashing.php`. Modern Laravel uses `Hash::make()` which supports algorithm switching (Argon2id, etc.).

**Severity:** Medium

---

### 2. LIKE Injection Vulnerability

**Location:** `ResourceService.php:265-269`

```php
$q->orWhere($column, 'LIKE', "%{$search}%");
```

**Issue:** Special characters `%` and `_` in search terms are not escaped. While not SQL injection, this can cause:
- Performance degradation with strategic `%` placement
- Unexpected match results

**Fix:**
```php
$escapedSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
$q->orWhere($column, 'LIKE', "%{$escapedSearch}%");
```

**Severity:** Medium

---

### 3. No Soft Delete Support

**Impact Areas:**
- `Resource` base class has no soft delete awareness
- `ResourceService::bulkDestroy()` performs hard deletes
- No `withTrashed()` query scope
- No restore functionality (despite `Authorizable` defining `canRestore()`)

**Severity:** High (data loss risk)

---

### 4. No Pagination Limit

**Location:** `ResourceService.php:65`

```php
$perPage = $params['perPage'] ?? $this->resource::$perPage;
```

**Issue:** Malicious clients can request `perPage=1000000`, causing memory exhaustion.

**Fix:**
```php
$perPage = min($params['perPage'] ?? $this->resource::$perPage, 100);
```

**Severity:** High (DoS vector)

---

### 5. Duplicate Transform Logic

**Locations:**
- `Resource.php:219-233`
- `ResourceService.php:80-97`

Both implement nearly identical model transformation. This violates DRY and risks inconsistencies.

**Severity:** Low (maintainability)

---

### 6. Fragile Method Existence Checks

**Pattern Found Throughout:**

```php
if (method_exists($user, 'hasPermission')) {...}
if (method_exists($user, 'isSuperAdmin')) {...}
if (method_exists($model, 'relationName')) {...}
```

**Issue:** No compile-time guarantees. Should use interfaces.

**Recommendation:**
```php
interface HasPermissions {
    public function hasPermission(string $permission): bool;
}

interface Identifiable {
    public function isSuperAdmin(): bool;
}
```

**Severity:** Medium (maintainability/reliability)

---

### 7. Static Property Overuse

The `Resource` class relies heavily on static properties:

```php
public static string $model;
public static string $label;
public static array $search = [];
public static int $perPage = 15;
```

**Issues:**
- Harder to test (global state)
- Can't have multiple configurations of the same resource
- Prevents dependency injection patterns

**Severity:** Low (architecture preference)

---

## Missing Features (vs Nova)

| Feature | Nova | Laravel Studio | Notes |
|---------|------|----------------|-------|
| Field-level `canSee()` | ✅ | ❌ | Fields can't have authorization callbacks |
| Lenses | ✅ | ❌ | Alternative resource views |
| Metrics/Trends | ✅ | Partial | Basic card support only |
| Computed Fields | ✅ | ❌ | Read-only calculated fields |
| Context-aware Validation | ✅ | ❌ | Different rules for create vs update per-field |
| Resource Replication | ✅ | ❌ | "Duplicate" action for cloning |
| Inline Creation | ✅ | ❌ | Create related without leaving form |
| Custom Tools | ✅ | ❌ | Standalone tool pages |
| Field Dependencies (advanced) | ✅ | Partial | Basic `dependsOn` only |
| Searchable Relations | ✅ | Partial | Limited relation search |

---

## Test Coverage Analysis

### Files Found (20 total)

```
tests/
├── Feature/
│   ├── BulkOperationsTest.php
│   ├── CardControllerTest.php
│   ├── GlobalSearchControllerTest.php
│   ├── MakeCommandsTest.php
│   ├── PanelControllerTest.php
│   ├── PanelManagementSecurityTest.php
│   └── ResourceControllerTest.php
├── Unit/
│   ├── ApiResponseTraitTest.php
│   ├── FieldTest.php
│   ├── FieldVisibilityTest.php
│   ├── MediaFieldSecurityTest.php
│   ├── QueryPerformanceTest.php
│   ├── ResourceServiceTest.php
│   ├── ResourceTest.php
│   └── StudioExceptionTest.php
└── Fixtures/
    ├── TestModel.php
    ├── TestResource.php
    └── TestModelFactory.php
```

### Coverage Gaps

| Area | Status | Risk |
|------|--------|------|
| `Authorizable` trait | ❌ Not tested | High |
| `GlobalSearchService` | ❌ Not tested | Medium |
| Permission denial flows | ❌ Not tested | High |
| Full auth lifecycle | ❌ Not tested | High |
| Vue components | ❌ Not tested | Medium |
| Error response consistency | ❌ Not tested | Low |

### Estimated Coverage: 60-70%

Core functionality is tested, but critical authorization paths lack coverage.

---

## API Design Issues

### 1. Inconsistent Error Responses

The controller mixes `abort()` and `response()->json()`:

```php
// Sometimes
abort(403, 'Unauthorized');

// Other times
return response()->json(['error' => 'Not found'], 404);
```

**Recommendation:** Create a consistent error response format via exception handler.

### 2. No API Versioning

Current routes:
```
/api/studio/panels/{panel}/resources/{resource}
```

Should be:
```
/api/v1/studio/panels/{panel}/resources/{resource}
```

Breaking changes will be painful without versioning.

### 3. No Rate Limiting

Bulk operations and global search have no rate limiting, creating potential abuse vectors.

---

## Documentation Accuracy

### Claims vs Reality

| Claim | Reality |
|-------|---------|
| "6 backend services" | ✅ Accurate |
| "21 field types" | ✅ Accurate |
| "5 Pinia stores" | ⚠️ In starter, not package |
| "56 Vue components" | ⚠️ In starter, not package |
| "Production-ready" | ❌ Needs hardening |

### Conflation Issue

The `CLAUDE.md` documentation conflates the **package** with the **starter template**. Users may expect the Vue components to be part of the core package when they're actually in `starters/default/frontend/`.

---

## Dependency Concerns

### Required Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "illuminate/support": "^12.0",
    "illuminate/database": "^12.0",
    "illuminate/http": "^12.0",
    "illuminate/console": "^12.0",
    "spatie/laravel-medialibrary": "^11.0"
  }
}
```

### Issues

1. **Spatie Media Library is Heavy**: Users not needing media upload still get this 2MB+ dependency. Consider making it optional via a separate `laravel-studio-media` package.

2. **Laravel 12 Only**: No support for Laravel 10/11 LTS versions.

---

## Recommendations

### Priority 1: Security Hardening

- [ ] Replace `bcrypt()` with `Hash::make()`
- [ ] Add LIKE character escaping in search
- [ ] Enforce pagination limits (max 100)
- [ ] Add rate limiting to bulk operations

### Priority 2: Feature Completeness

- [ ] Add soft delete support with restore functionality
- [ ] Implement field-level authorization (`canSee()`)
- [ ] Add context-aware validation (create vs update)

### Priority 3: Code Quality

- [ ] Define interfaces for `HasPermissions`, `Identifiable`
- [ ] Extract shared transform logic to dedicated class
- [ ] Add API versioning to routes

### Priority 4: Testing

- [ ] Add authorization flow tests
- [ ] Add permission denial tests
- [ ] Add integration tests for full request lifecycle
- [ ] Consider frontend component testing

### Priority 5: Documentation

- [ ] Clarify package vs starter template scope
- [ ] Add upgrade/migration guide
- [ ] Document all security considerations

---

## Score Breakdown

| Category | Score | Weight | Weighted |
|----------|-------|--------|----------|
| Code Quality | 8/10 | 20% | 1.6 |
| Security | 7/10 | 25% | 1.75 |
| Test Coverage | 6/10 | 20% | 1.2 |
| Feature Completeness | 7/10 | 15% | 1.05 |
| Documentation | 7/10 | 10% | 0.7 |
| API Design | 7/10 | 10% | 0.7 |
| **Total** | | 100% | **7.0/10** |

---

## Conclusion

Laravel Studio is a **promising open-source Nova alternative** with solid architectural foundations. The fluent API, service layer separation, and security awareness demonstrate good engineering practice.

However, the package requires additional hardening before production use:

1. **Security gaps** (pagination limits, LIKE escaping) need immediate attention
2. **Missing soft delete support** is a significant omission
3. **Test coverage** of authorization flows is insufficient
4. **Documentation** overstates capabilities by conflating package and starter

**Verdict:** Suitable for internal tools and non-critical applications. Requires hardening for production systems handling sensitive data.

---

*This review is based on static code analysis. Runtime behavior may differ.*
