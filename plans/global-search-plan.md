# Global Search - Laravel Studio Package

## Overview

**Purpose:** Search across all resources from a single search box (Cmd+K / Ctrl+K command palette).

**Dependencies:** None

---

## API Design

### Resource Configuration

```php
// In Resource class
class UserResource extends Resource
{
    /**
     * Enable global search for this resource
     */
    public static function globallySearchable(): bool
    {
        return true;
    }

    /**
     * Columns to search in
     */
    public static function globallySearchableColumns(): array
    {
        return ['name', 'email'];
    }

    /**
     * How to display in search results
     */
    public static function globalSearchResult($model): array
    {
        return [
            'title' => $model->name,
            'subtitle' => $model->email,
            'avatar' => $model->avatar_url,
            'meta' => "ID: {$model->id}",
        ];
    }

    /**
     * Max results to show for this resource
     */
    public static function globalSearchResultsLimit(): int
    {
        return 5;
    }

    /**
     * URL to navigate to when result is clicked
     */
    public static function globalSearchResultUrl($model): string
    {
        return "/admin/users/{$model->id}";
    }
}
```

### Configuration

```php
// config/studio.php
'global_search' => [
    // Enable/disable global search
    'enabled' => true,

    // Minimum characters before search starts
    'min_characters' => 2,

    // Debounce delay in milliseconds
    'debounce_ms' => 300,

    // Maximum total results across all resources
    'max_results' => 20,

    // Cache search results (seconds, 0 to disable)
    'cache_ttl' => 0,

    // Show recent searches
    'show_recent' => true,
    'recent_limit' => 5,
],
```

---

## Backend Implementation

### File Structure

```
packages/laravel-studio/
├── src/
│   ├── Services/
│   │   └── GlobalSearchService.php
│   ├── Http/Controllers/
│   │   └── GlobalSearchController.php
│   └── Traits/
│       └── GloballySearchable.php
└── config/studio.php
```

### GloballySearchable Trait

```php
// src/Traits/GloballySearchable.php
namespace SavyApps\LaravelStudio\Traits;

trait GloballySearchable
{
    /**
     * Enable global search for this resource
     */
    public static function globallySearchable(): bool
    {
        return true;
    }

    /**
     * Columns to search in
     * Override in resource to customize
     */
    public static function globallySearchableColumns(): array
    {
        // Default to searchable columns defined in resource
        if (method_exists(static::class, 'searchableColumns')) {
            return static::searchableColumns();
        }

        return ['id'];
    }

    /**
     * Format a model for search results
     * Override in resource to customize
     */
    public static function globalSearchResult($model): array
    {
        // Try to find a good title
        $title = $model->name
            ?? $model->title
            ?? $model->label
            ?? "#{$model->id}";

        // Try to find a subtitle
        $subtitle = $model->email
            ?? $model->description
            ?? $model->slug
            ?? null;

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'avatar' => null,
            'meta' => null,
        ];
    }

    /**
     * Maximum results for this resource
     */
    public static function globalSearchResultsLimit(): int
    {
        return 5;
    }

    /**
     * URL when result is clicked
     */
    public static function globalSearchResultUrl($model): string
    {
        $panel = request()->get('_panel', 'admin');
        $resourceKey = static::uriKey();

        return "/{$panel}/{$resourceKey}/{$model->getKey()}";
    }

    /**
     * Search query scope
     * Can be overridden for custom search logic
     */
    public static function globalSearchQuery($query, string $search): void
    {
        $columns = static::globallySearchableColumns();

        $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                // Handle relationship columns (e.g., 'roles.name')
                if (str_contains($column, '.')) {
                    [$relation, $relationColumn] = explode('.', $column, 2);
                    $q->orWhereHas($relation, function ($rq) use ($relationColumn, $search) {
                        $rq->where($relationColumn, 'like', "%{$search}%");
                    });
                } else {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            }
        });
    }
}
```

### Global Search Service

```php
// src/Services/GlobalSearchService.php
namespace SavyApps\LaravelStudio\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GlobalSearchService
{
    /**
     * Search across all resources
     */
    public function search(string $query, ?string $panel = null): array
    {
        if (strlen($query) < config('studio.global_search.min_characters', 2)) {
            return [];
        }

        $cacheKey = $this->getCacheKey($query, $panel);
        $cacheTtl = config('studio.global_search.cache_ttl', 0);

        if ($cacheTtl > 0) {
            return Cache::remember($cacheKey, $cacheTtl, fn() => $this->performSearch($query, $panel));
        }

        return $this->performSearch($query, $panel);
    }

    /**
     * Perform the actual search
     */
    protected function performSearch(string $query, ?string $panel): array
    {
        $resources = $this->getSearchableResources($panel);
        $maxResults = config('studio.global_search.max_results', 20);
        $results = [];

        foreach ($resources as $key => $resourceClass) {
            if (!$this->isSearchable($resourceClass)) {
                continue;
            }

            $resourceResults = $this->searchResource($resourceClass, $query);

            if (!empty($resourceResults)) {
                $results[] = [
                    'resource' => $key,
                    'label' => $resourceClass::label(),
                    'icon' => $resourceClass::icon() ?? 'folder',
                    'results' => $resourceResults,
                ];
            }

            // Check if we've hit max results
            $totalResults = collect($results)->sum(fn($r) => count($r['results']));
            if ($totalResults >= $maxResults) {
                break;
            }
        }

        return $results;
    }

    /**
     * Search within a single resource
     */
    protected function searchResource(string $resourceClass, string $query): array
    {
        $model = $resourceClass::model();
        $limit = $resourceClass::globalSearchResultsLimit();

        $queryBuilder = $model::query();

        // Apply search
        $resourceClass::globalSearchQuery($queryBuilder, $query);

        // Apply any default scopes
        if (method_exists($resourceClass, 'indexQuery')) {
            $resourceClass::indexQuery($queryBuilder);
        }

        $models = $queryBuilder->limit($limit)->get();

        return $models->map(function ($model) use ($resourceClass) {
            $result = $resourceClass::globalSearchResult($model);

            return array_merge($result, [
                'id' => $model->getKey(),
                'url' => $resourceClass::globalSearchResultUrl($model),
            ]);
        })->toArray();
    }

    /**
     * Get searchable resources for a panel
     */
    protected function getSearchableResources(?string $panel): array
    {
        $allResources = config('studio.resources', []);

        if ($panel) {
            $panelResources = config("studio.panels.{$panel}.resources", []);
            return collect($allResources)
                ->filter(fn($class, $key) => in_array($key, $panelResources))
                ->toArray();
        }

        return $allResources;
    }

    /**
     * Check if a resource is searchable
     */
    protected function isSearchable(string $resourceClass): bool
    {
        if (!method_exists($resourceClass, 'globallySearchable')) {
            return true; // Default to searchable
        }

        return $resourceClass::globallySearchable();
    }

    /**
     * Get cache key for search query
     */
    protected function getCacheKey(string $query, ?string $panel): string
    {
        $userId = auth()->id();
        return "global_search:{$userId}:{$panel}:" . md5($query);
    }
}
```

### Global Search Controller

```php
// src/Http/Controllers/GlobalSearchController.php
namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SavyApps\LaravelStudio\Services\GlobalSearchService;

class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    /**
     * Perform global search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'panel' => 'nullable|string',
        ]);

        $query = $request->input('q');
        $panel = $request->input('panel');

        $results = $this->searchService->search($query, $panel);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total' => collect($results)->sum(fn($r) => count($r['results'])),
        ]);
    }

    /**
     * Get recent searches for current user
     */
    public function recent(Request $request): JsonResponse
    {
        if (!config('studio.global_search.show_recent', true)) {
            return response()->json(['recent' => []]);
        }

        $limit = config('studio.global_search.recent_limit', 5);

        // Get from user settings or session
        $recent = $request->user()->settings['recent_searches'] ?? [];

        return response()->json([
            'recent' => array_slice($recent, 0, $limit),
        ]);
    }

    /**
     * Save a search to recent
     */
    public function saveRecent(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
            'result' => 'nullable|array',
        ]);

        $user = $request->user();
        $recent = $user->settings['recent_searches'] ?? [];
        $limit = config('studio.global_search.recent_limit', 5);

        // Add to beginning, remove duplicates
        $newEntry = [
            'query' => $request->input('query'),
            'result' => $request->input('result'),
            'timestamp' => now()->toIso8601String(),
        ];

        $recent = collect($recent)
            ->filter(fn($r) => $r['query'] !== $newEntry['query'])
            ->prepend($newEntry)
            ->take($limit)
            ->values()
            ->toArray();

        // Save to user settings
        $user->update([
            'settings' => array_merge($user->settings ?? [], [
                'recent_searches' => $recent,
            ]),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Clear recent searches
     */
    public function clearRecent(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'settings' => array_merge($user->settings ?? [], [
                'recent_searches' => [],
            ]),
        ]);

        return response()->json(['success' => true]);
    }
}
```

---

## Frontend Implementation

### File Structure

```
packages/laravel-studio/resources/js/
├── components/
│   └── search/
│       ├── GlobalSearch.vue            # Main search input (navbar)
│       ├── SearchPalette.vue           # Cmd+K modal
│       ├── SearchResults.vue           # Results list
│       ├── SearchResultItem.vue        # Single result item
│       └── RecentSearches.vue          # Recent searches list
├── composables/
│   └── useGlobalSearch.js
└── services/
    └── searchService.js
```

### Search Service

```javascript
// services/searchService.js
import api from '@/services/api'

export const searchService = {
  /**
   * Perform global search
   */
  async search(query, panel = null) {
    const response = await api.get('/api/search', {
      params: { q: query, panel },
    })
    return response.data
  },

  /**
   * Get recent searches
   */
  async getRecent() {
    const response = await api.get('/api/search/recent')
    return response.data.recent
  },

  /**
   * Save a search to recent
   */
  async saveRecent(query, result = null) {
    await api.post('/api/search/recent', { query, result })
  },

  /**
   * Clear recent searches
   */
  async clearRecent() {
    await api.delete('/api/search/recent')
  },
}

export default searchService
```

### useGlobalSearch Composable

```javascript
// composables/useGlobalSearch.js
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { searchService } from '@/services/searchService'
import { useDebounceFn } from '@vueuse/core'

export function useGlobalSearch() {
  const router = useRouter()

  const isOpen = ref(false)
  const query = ref('')
  const results = ref([])
  const recentSearches = ref([])
  const loading = ref(false)
  const selectedIndex = ref(0)

  // Flatten results for keyboard navigation
  const flatResults = computed(() => {
    return results.value.flatMap(group =>
      group.results.map(result => ({
        ...result,
        resource: group.resource,
        resourceLabel: group.label,
        resourceIcon: group.icon,
      }))
    )
  })

  const hasResults = computed(() => flatResults.value.length > 0)

  // Debounced search
  const performSearch = useDebounceFn(async (searchQuery) => {
    if (searchQuery.length < 2) {
      results.value = []
      return
    }

    loading.value = true
    try {
      const response = await searchService.search(searchQuery)
      results.value = response.results
      selectedIndex.value = 0
    } catch (error) {
      console.error('Search failed:', error)
      results.value = []
    } finally {
      loading.value = false
    }
  }, 300)

  // Watch query changes
  watch(query, (newQuery) => {
    if (newQuery) {
      performSearch(newQuery)
    } else {
      results.value = []
    }
  })

  // Open search palette
  const open = async () => {
    isOpen.value = true
    query.value = ''
    results.value = []
    selectedIndex.value = 0

    // Load recent searches
    try {
      recentSearches.value = await searchService.getRecent()
    } catch {
      recentSearches.value = []
    }
  }

  // Close search palette
  const close = () => {
    isOpen.value = false
    query.value = ''
    results.value = []
  }

  // Navigate to result
  const goToResult = async (result) => {
    // Save to recent
    try {
      await searchService.saveRecent(query.value, {
        title: result.title,
        url: result.url,
        resource: result.resource,
      })
    } catch {
      // Ignore errors
    }

    close()
    router.push(result.url)
  }

  // Keyboard navigation
  const handleKeydown = (event) => {
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault()
        selectedIndex.value = Math.min(
          selectedIndex.value + 1,
          flatResults.value.length - 1
        )
        break

      case 'ArrowUp':
        event.preventDefault()
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
        break

      case 'Enter':
        event.preventDefault()
        if (flatResults.value[selectedIndex.value]) {
          goToResult(flatResults.value[selectedIndex.value])
        }
        break

      case 'Escape':
        close()
        break
    }
  }

  // Global keyboard shortcut (Cmd+K / Ctrl+K)
  const handleGlobalKeydown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
      event.preventDefault()
      if (isOpen.value) {
        close()
      } else {
        open()
      }
    }
  }

  // Clear recent searches
  const clearRecent = async () => {
    await searchService.clearRecent()
    recentSearches.value = []
  }

  return {
    isOpen,
    query,
    results,
    flatResults,
    recentSearches,
    loading,
    selectedIndex,
    hasResults,
    open,
    close,
    goToResult,
    handleKeydown,
    handleGlobalKeydown,
    clearRecent,
  }
}
```

### Search Palette Component

```vue
<!-- components/search/SearchPalette.vue -->
<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useGlobalSearch } from '@/composables/useGlobalSearch'
import SearchResults from './SearchResults.vue'
import RecentSearches from './RecentSearches.vue'
import Icon from '@/components/common/Icon.vue'

const {
  isOpen,
  query,
  results,
  flatResults,
  recentSearches,
  loading,
  selectedIndex,
  hasResults,
  open,
  close,
  goToResult,
  handleKeydown,
  handleGlobalKeydown,
  clearRecent,
} = useGlobalSearch()

const inputRef = ref(null)

// Focus input when opening
const focusInput = () => {
  nextTick(() => {
    inputRef.value?.focus()
  })
}

// Register global keyboard shortcut
onMounted(() => {
  document.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleGlobalKeydown)
})

// Focus input when opened
watch(isOpen, (open) => {
  if (open) focusInput()
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="search-palette-overlay"
        @click="close"
      >
        <div
          class="search-palette"
          @click.stop
          @keydown="handleKeydown"
        >
          <!-- Search Input -->
          <div class="search-input-wrapper">
            <Icon name="search" class="search-icon" />
            <input
              ref="inputRef"
              v-model="query"
              type="text"
              placeholder="Search..."
              class="search-input"
            />
            <div class="search-shortcut">
              <kbd>ESC</kbd> to close
            </div>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="search-loading">
            Searching...
          </div>

          <!-- Results -->
          <SearchResults
            v-else-if="query && hasResults"
            :results="results"
            :flat-results="flatResults"
            :selected-index="selectedIndex"
            @select="goToResult"
          />

          <!-- No Results -->
          <div v-else-if="query && !hasResults" class="search-empty">
            No results found for "{{ query }}"
          </div>

          <!-- Recent Searches (when no query) -->
          <RecentSearches
            v-else-if="!query && recentSearches.length"
            :recent="recentSearches"
            @select="goToResult"
            @clear="clearRecent"
          />

          <!-- Empty State -->
          <div v-else class="search-empty">
            Start typing to search...
          </div>

          <!-- Footer -->
          <div class="search-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> to navigate</span>
            <span><kbd>↵</kbd> to select</span>
            <span><kbd>ESC</kbd> to close</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.search-palette-overlay {
  @apply fixed inset-0 bg-black/50 flex items-start justify-center pt-20 z-50;
}

.search-palette {
  @apply bg-white rounded-lg shadow-2xl w-full max-w-2xl overflow-hidden;
}

.search-input-wrapper {
  @apply flex items-center gap-3 px-4 py-3 border-b;
}

.search-icon {
  @apply w-5 h-5 text-gray-400;
}

.search-input {
  @apply flex-1 outline-none text-lg;
}

.search-shortcut {
  @apply text-xs text-gray-400;
}

.search-shortcut kbd {
  @apply bg-gray-100 px-1.5 py-0.5 rounded text-gray-600;
}

.search-loading,
.search-empty {
  @apply px-4 py-8 text-center text-gray-500;
}

.search-footer {
  @apply flex items-center justify-center gap-4 px-4 py-2 bg-gray-50 text-xs text-gray-500 border-t;
}

.search-footer kbd {
  @apply bg-gray-200 px-1.5 py-0.5 rounded text-gray-600 mx-0.5;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  @apply transition-opacity duration-200;
}

.fade-enter-from,
.fade-leave-to {
  @apply opacity-0;
}
</style>
```

### Search Results Component

```vue
<!-- components/search/SearchResults.vue -->
<script setup>
import SearchResultItem from './SearchResultItem.vue'
import Icon from '@/components/common/Icon.vue'

const props = defineProps({
  results: {
    type: Array,
    required: true,
  },
  flatResults: {
    type: Array,
    required: true,
  },
  selectedIndex: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['select'])

// Calculate if a result is selected
const isSelected = (result) => {
  const index = props.flatResults.findIndex(
    r => r.id === result.id && r.resource === result.resource
  )
  return index === props.selectedIndex
}
</script>

<template>
  <div class="search-results">
    <div
      v-for="group in results"
      :key="group.resource"
      class="result-group"
    >
      <div class="group-header">
        <Icon :name="group.icon" class="w-4 h-4" />
        <span>{{ group.label }}</span>
      </div>

      <div class="group-items">
        <SearchResultItem
          v-for="result in group.results"
          :key="result.id"
          :result="result"
          :selected="isSelected({ ...result, resource: group.resource })"
          @click="emit('select', { ...result, resource: group.resource })"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.search-results {
  @apply max-h-96 overflow-y-auto;
}

.result-group {
  @apply py-2;
}

.group-header {
  @apply flex items-center gap-2 px-4 py-1 text-xs font-medium text-gray-500 uppercase;
}

.group-items {
  @apply space-y-0.5;
}
</style>
```

### Search Result Item Component

```vue
<!-- components/search/SearchResultItem.vue -->
<script setup>
import Icon from '@/components/common/Icon.vue'

const props = defineProps({
  result: {
    type: Object,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['click'])
</script>

<template>
  <div
    :class="[
      'result-item',
      { 'result-item--selected': selected }
    ]"
    @click="emit('click')"
  >
    <!-- Avatar -->
    <div v-if="result.avatar" class="result-avatar">
      <img :src="result.avatar" :alt="result.title" />
    </div>
    <div v-else class="result-avatar result-avatar--placeholder">
      <Icon name="file" class="w-4 h-4" />
    </div>

    <!-- Content -->
    <div class="result-content">
      <div class="result-title">{{ result.title }}</div>
      <div v-if="result.subtitle" class="result-subtitle">
        {{ result.subtitle }}
      </div>
    </div>

    <!-- Meta -->
    <div v-if="result.meta" class="result-meta">
      {{ result.meta }}
    </div>

    <!-- Arrow -->
    <Icon name="chevron-right" class="result-arrow" />
  </div>
</template>

<style scoped>
.result-item {
  @apply flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-gray-50;
}

.result-item--selected {
  @apply bg-blue-50;
}

.result-avatar {
  @apply w-10 h-10 rounded-full overflow-hidden flex-shrink-0;
}

.result-avatar img {
  @apply w-full h-full object-cover;
}

.result-avatar--placeholder {
  @apply bg-gray-100 flex items-center justify-center text-gray-400;
}

.result-content {
  @apply flex-1 min-w-0;
}

.result-title {
  @apply text-sm font-medium text-gray-900 truncate;
}

.result-subtitle {
  @apply text-xs text-gray-500 truncate;
}

.result-meta {
  @apply text-xs text-gray-400;
}

.result-arrow {
  @apply w-4 h-4 text-gray-300;
}
</style>
```

### Global Search Trigger (Navbar)

```vue
<!-- components/search/GlobalSearch.vue -->
<script setup>
import { useGlobalSearch } from '@/composables/useGlobalSearch'
import SearchPalette from './SearchPalette.vue'
import Icon from '@/components/common/Icon.vue'

const { open } = useGlobalSearch()
</script>

<template>
  <button
    @click="open"
    class="global-search-trigger"
  >
    <Icon name="search" class="w-4 h-4" />
    <span class="search-text">Search...</span>
    <kbd class="search-kbd">⌘K</kbd>
  </button>

  <SearchPalette />
</template>

<style scoped>
.global-search-trigger {
  @apply flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-500 text-sm transition-colors;
}

.search-text {
  @apply hidden sm:inline;
}

.search-kbd {
  @apply hidden sm:inline bg-white px-1.5 py-0.5 rounded text-xs text-gray-400 border;
}
</style>
```

---

## Routes

```php
// Register in ServiceProvider
Route::prefix('api/search')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::get('/', [GlobalSearchController::class, 'search']);
        Route::get('/recent', [GlobalSearchController::class, 'recent']);
        Route::post('/recent', [GlobalSearchController::class, 'saveRecent']);
        Route::delete('/recent', [GlobalSearchController::class, 'clearRecent']);
    });
```

---

## Integration with Layout

```vue
<!-- In Navbar.vue -->
<template>
  <nav class="navbar">
    <div class="navbar-left">
      <!-- Logo, etc. -->
    </div>

    <div class="navbar-center">
      <!-- Global Search -->
      <GlobalSearch />
    </div>

    <div class="navbar-right">
      <!-- User dropdown, etc. -->
    </div>
  </nav>
</template>

<script setup>
import GlobalSearch from '@/components/search/GlobalSearch.vue'
</script>
```

---

## Implementation Checklist

### Backend
- [ ] Create GloballySearchable trait
- [ ] Create GlobalSearchService
- [ ] Create GlobalSearchController
- [ ] Add routes
- [ ] Update config/studio.php
- [ ] Register in ServiceProvider
- [ ] Add search columns to existing resources

### Frontend
- [ ] Create searchService.js
- [ ] Create useGlobalSearch composable
- [ ] Create GlobalSearch component (trigger)
- [ ] Create SearchPalette component
- [ ] Create SearchResults component
- [ ] Create SearchResultItem component
- [ ] Create RecentSearches component
- [ ] Add to Navbar
- [ ] Style components

### Testing
- [ ] Unit tests for GlobalSearchService
- [ ] Feature tests for GlobalSearchController
- [ ] Test keyboard navigation
- [ ] Test recent searches
