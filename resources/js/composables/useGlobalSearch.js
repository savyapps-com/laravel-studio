import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import searchService from '../services/searchService'

/**
 * Composable for global search functionality
 */
export function useGlobalSearch(options = {}) {
  const {
    debounceMs = 300,
    minCharacters = 2,
    autoFocus = true,
    panel = null
  } = options

  // State
  const isOpen = ref(false)
  const query = ref('')
  const results = ref([])
  const groupedResults = ref({})
  const totalResults = ref(0)
  const loading = ref(false)
  const error = ref(null)
  const selectedIndex = ref(0)
  const recentSearches = ref([])
  const searchableResources = ref([])

  // Debounce timer
  let debounceTimer = null

  // Computed
  const hasQuery = computed(() => query.value.length >= minCharacters)
  const hasResults = computed(() => results.value.length > 0)
  const isEmpty = computed(() => hasQuery.value && !loading.value && !hasResults.value)
  const showRecent = computed(() => !hasQuery.value && recentSearches.value.length > 0)

  const flatResults = computed(() => {
    // Flatten grouped results for keyboard navigation
    const flat = []
    Object.values(groupedResults.value).forEach(group => {
      group.results.forEach(result => {
        flat.push({
          ...result,
          resourceKey: group.key,
          resourceLabel: group.label
        })
      })
    })
    return flat
  })

  const selectedResult = computed(() => flatResults.value[selectedIndex.value] || null)

  // Methods
  const open = () => {
    isOpen.value = true
    loadRecentSearches()
    loadSearchableResources()
  }

  const close = () => {
    isOpen.value = false
    query.value = ''
    results.value = []
    groupedResults.value = {}
    selectedIndex.value = 0
    error.value = null
  }

  const toggle = () => {
    if (isOpen.value) {
      close()
    } else {
      open()
    }
  }

  const search = async () => {
    if (!hasQuery.value) {
      results.value = []
      groupedResults.value = {}
      totalResults.value = 0
      return
    }

    loading.value = true
    error.value = null

    try {
      const response = await searchService.search(query.value, { panel })
      results.value = response.results
      groupedResults.value = response.grouped
      totalResults.value = response.total
      selectedIndex.value = 0

      // Store search locally
      searchService.storeLocalSearch(query.value)
    } catch (err) {
      error.value = err.message || 'Search failed'
      results.value = []
      groupedResults.value = {}
    } finally {
      loading.value = false
    }
  }

  const debouncedSearch = () => {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
    debounceTimer = setTimeout(search, debounceMs)
  }

  const loadRecentSearches = () => {
    recentSearches.value = searchService.getLocalRecentSearches(5)
  }

  const loadSearchableResources = async () => {
    try {
      const response = await searchService.getSearchableResources(panel)
      searchableResources.value = response.resources || []
    } catch {
      // Ignore errors
    }
  }

  const clearRecentSearches = () => {
    searchService.clearLocalRecentSearches()
    recentSearches.value = []
  }

  const selectRecentSearch = (searchQuery) => {
    query.value = searchQuery
    search()
  }

  const selectResult = (result) => {
    if (result?.url) {
      window.location.href = result.url
    }
    close()
  }

  const navigateUp = () => {
    if (selectedIndex.value > 0) {
      selectedIndex.value--
    }
  }

  const navigateDown = () => {
    if (selectedIndex.value < flatResults.value.length - 1) {
      selectedIndex.value++
    }
  }

  const selectCurrent = () => {
    if (selectedResult.value) {
      selectResult(selectedResult.value)
    }
  }

  const handleKeydown = (event) => {
    if (!isOpen.value) return

    switch (event.key) {
      case 'ArrowUp':
        event.preventDefault()
        navigateUp()
        break
      case 'ArrowDown':
        event.preventDefault()
        navigateDown()
        break
      case 'Enter':
        event.preventDefault()
        selectCurrent()
        break
      case 'Escape':
        event.preventDefault()
        close()
        break
    }
  }

  const handleGlobalKeydown = (event) => {
    // Check for Cmd+K / Ctrl+K
    if (searchService.isShortcutPressed(event)) {
      event.preventDefault()
      toggle()
    }
  }

  // Watch query changes
  watch(query, () => {
    if (hasQuery.value) {
      debouncedSearch()
    } else {
      results.value = []
      groupedResults.value = {}
      totalResults.value = 0
    }
  })

  // Setup global keyboard listener
  onMounted(() => {
    document.addEventListener('keydown', handleGlobalKeydown)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown)
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
  })

  return {
    // State
    isOpen,
    query,
    results,
    groupedResults,
    totalResults,
    loading,
    error,
    selectedIndex,
    recentSearches,
    searchableResources,

    // Computed
    hasQuery,
    hasResults,
    isEmpty,
    showRecent,
    flatResults,
    selectedResult,

    // Methods
    open,
    close,
    toggle,
    search,
    loadRecentSearches,
    clearRecentSearches,
    selectRecentSearch,
    selectResult,
    navigateUp,
    navigateDown,
    selectCurrent,
    handleKeydown,

    // Utilities
    highlightMatches: searchService.highlightMatches.bind(searchService),
    getShortcutDisplay: searchService.getShortcutDisplay
  }
}

export default useGlobalSearch
