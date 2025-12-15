/**
 * Laravel Studio - Global Search Components
 *
 * Global search palette and result components.
 * Import separately when global search is needed.
 *
 * Usage:
 * import { SearchPalette, useGlobalSearch } from 'laravel-studio/search'
 */

// Search Components
export { default as SearchPalette } from '../components/search/SearchPalette.vue'
export { default as SearchResultItem } from '../components/search/SearchResultItem.vue'

// Search Service
export { searchService } from '../services/searchService.js'

// Search Composable
export { useGlobalSearch } from '../composables/useGlobalSearch.js'
