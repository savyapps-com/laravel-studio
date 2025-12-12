<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 overflow-y-auto"
        style="z-index: 9999;"
        @click.self="close"
      >
        <!-- Backdrop with blur -->
        <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm" />

        <!-- Modal Container - Full screen on mobile, centered on desktop -->
        <div class="fixed inset-0 flex items-start justify-center p-0 sm:p-4 sm:pt-[10vh]">
          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="isOpen"
              class="relative w-full h-full sm:h-auto sm:max-w-2xl sm:rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700/50 flex flex-col overflow-hidden"
            >
              <!-- Search Input Section -->
              <div class="flex items-center gap-3 px-4 sm:px-5 py-4 border-b border-gray-200 dark:border-gray-700/50">
                <!-- Search Icon -->
                <div class="flex-shrink-0">
                  <svg
                    class="w-5 h-5 text-gray-400 dark:text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                </div>

                <!-- Input -->
                <input
                  ref="inputRef"
                  v-model="query"
                  type="text"
                  class="flex-1 bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-0 focus:outline-none text-base sm:text-lg"
                  placeholder="Search anything..."
                  @keydown="handleKeydown"
                />

                <!-- Right side actions -->
                <div class="flex items-center gap-2">
                  <!-- Clear button -->
                  <button
                    v-if="query"
                    @click="query = ''"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>

                  <!-- Keyboard shortcut badge -->
                  <kbd
                    v-if="!query"
                    class="hidden sm:inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-lg"
                  >
                    <span class="text-[10px]">ESC</span>
                  </kbd>

                  <!-- Close button (mobile) -->
                  <button
                    @click="close"
                    class="sm:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Content Area -->
              <div class="flex-1 overflow-y-auto overscroll-contain min-h-[200px] max-h-[60vh] sm:max-h-[400px]">
                <!-- Loading State -->
                <div v-if="loading" class="flex flex-col items-center justify-center py-12 px-4">
                  <div class="w-10 h-10 border-3 border-primary-500/30 border-t-primary-500 rounded-full animate-spin"></div>
                  <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Searching...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="isEmpty" class="flex flex-col items-center justify-center py-12 px-4 text-center">
                  <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <p class="text-base font-medium text-gray-900 dark:text-white">No results found</p>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try adjusting your search terms
                  </p>
                </div>

                <!-- Recent Searches -->
                <div v-else-if="showRecent" class="py-2">
                  <div class="flex items-center justify-between px-4 sm:px-5 py-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                      Recent Searches
                    </span>
                    <button
                      @click="clearRecentSearches"
                      class="text-xs font-medium text-gray-400 dark:text-gray-500 hover:text-primary-500 dark:hover:text-primary-400 transition-colors"
                    >
                      Clear all
                    </button>
                  </div>
                  <ul class="px-2 sm:px-3">
                    <li
                      v-for="(search, index) in recentSearches"
                      :key="index"
                      @click="selectRecentSearch(search)"
                      class="group flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                      <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">{{ search }}</span>
                      <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </li>
                  </ul>
                </div>

                <!-- Search Results -->
                <div v-else-if="hasResults" class="py-2">
                  <div v-for="(group, key) in groupedResults" :key="key" class="mb-2">
                    <div class="px-4 sm:px-5 py-2">
                      <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ group.label }}
                      </span>
                    </div>
                    <ul class="px-2 sm:px-3">
                      <SearchResultItem
                        v-for="(result, index) in group.results"
                        :key="result.id"
                        :result="result"
                        :query="query"
                        :selected="isSelected(key, index)"
                        @select="selectResult(result)"
                      />
                    </ul>
                  </div>
                </div>

                <!-- Initial State with searchable resources -->
                <div v-else-if="searchableResources.length > 0" class="py-4 px-4 sm:px-5">
                  <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    Search in
                  </span>
                  <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <div
                      v-for="resource in searchableResources"
                      :key="resource.key"
                      class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-sm text-gray-600 dark:text-gray-400"
                    >
                      <span
                        v-if="resource.icon"
                        class="w-4 h-4 text-gray-400 dark:text-gray-500"
                        v-html="resource.icon"
                      ></span>
                      <span class="truncate">{{ resource.label }}</span>
                    </div>
                  </div>
                </div>

                <!-- Default empty state when no recent searches -->
                <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center">
                  <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-900/30 dark:to-secondary-900/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </div>
                  <p class="text-base font-medium text-gray-900 dark:text-white">Start searching</p>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Type to search across your data
                  </p>
                </div>
              </div>

              <!-- Footer with keyboard shortcuts -->
              <div class="hidden sm:flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700/50">
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                  <div class="flex items-center gap-1.5">
                    <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600 font-medium">↵</kbd>
                    <span>select</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600 font-medium">↑↓</kbd>
                    <span>navigate</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600 font-medium">esc</kbd>
                    <span>close</span>
                  </div>
                </div>
                <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                  <span>Powered by</span>
                  <span class="font-semibold text-primary-500">Search</span>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useGlobalSearch } from '../../composables/useGlobalSearch'
import SearchResultItem from './SearchResultItem.vue'

const props = defineProps({
  panel: {
    type: String,
    default: null
  },
  open: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:open', 'select'])

const inputRef = ref(null)

const {
  isOpen,
  query,
  loading,
  groupedResults,
  recentSearches,
  searchableResources,
  hasResults,
  isEmpty,
  showRecent,
  selectedIndex,
  flatResults,
  open: openSearch,
  close: closeSearch,
  clearRecentSearches,
  selectRecentSearch,
  selectResult: internalSelectResult,
  handleKeydown,
  getShortcutDisplay
} = useGlobalSearch({ panel: props.panel })

// Sync external open prop with internal isOpen
watch(() => props.open, (value) => {
  if (value && !isOpen.value) {
    openSearch()
  } else if (!value && isOpen.value) {
    closeSearch()
  }
})

// Emit update:open when internal state changes
watch(isOpen, (value) => {
  emit('update:open', value)
})

// Wrapper for close that also emits
const close = () => {
  closeSearch()
  emit('update:open', false)
}

// Wrapper for selectResult that also emits
const selectResult = (result) => {
  internalSelectResult(result)
  emit('select', result)
}

// Track selected item for highlighting
const isSelected = (groupKey, resultIndex) => {
  let currentIndex = 0
  for (const [key, group] of Object.entries(groupedResults.value)) {
    for (let i = 0; i < group.results.length; i++) {
      if (key === groupKey && i === resultIndex) {
        return currentIndex === selectedIndex.value
      }
      currentIndex++
    }
  }
  return false
}

// Focus input when modal opens
watch(isOpen, async (value) => {
  if (value) {
    await nextTick()
    inputRef.value?.focus()
  }
})

// Expose methods for parent components
defineExpose({
  open: openSearch,
  close
})
</script>
