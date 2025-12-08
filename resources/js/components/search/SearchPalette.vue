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
        class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" />

        <!-- Modal -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="isOpen"
            class="relative mx-auto max-w-xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5 transition-all"
          >
            <!-- Search Input -->
            <div class="relative">
              <svg
                class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                  clip-rule="evenodd"
                />
              </svg>
              <input
                ref="inputRef"
                v-model="query"
                type="text"
                class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm"
                placeholder="Search..."
                @keydown="handleKeydown"
              />
              <div class="absolute right-4 top-3.5 flex items-center gap-1">
                <kbd
                  v-if="!query"
                  class="hidden sm:inline-flex items-center rounded border border-gray-200 px-1.5 text-xs text-gray-400"
                >
                  {{ getShortcutDisplay() }}
                </kbd>
                <button
                  v-if="query"
                  class="text-gray-400 hover:text-gray-500"
                  @click="query = ''"
                >
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="px-6 py-14 text-center text-sm sm:px-14">
              <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-primary-600"></div>
              <p class="mt-4 text-gray-500">Searching...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="isEmpty" class="px-6 py-14 text-center text-sm sm:px-14">
              <svg
                class="mx-auto h-6 w-6 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"
                />
              </svg>
              <p class="mt-4 font-semibold text-gray-900">No results found</p>
              <p class="mt-2 text-gray-500">
                We couldn't find anything with that term. Please try again.
              </p>
            </div>

            <!-- Recent Searches -->
            <div v-else-if="showRecent" class="max-h-80 scroll-py-2 overflow-y-auto">
              <div class="p-2">
                <div class="flex items-center justify-between px-3 py-2">
                  <h2 class="text-xs font-semibold text-gray-500">Recent Searches</h2>
                  <button
                    class="text-xs text-gray-400 hover:text-gray-600"
                    @click="clearRecentSearches"
                  >
                    Clear
                  </button>
                </div>
                <ul>
                  <li
                    v-for="(search, index) in recentSearches"
                    :key="index"
                    class="cursor-pointer select-none rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="selectRecentSearch(search)"
                  >
                    <div class="flex items-center">
                      <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                      </svg>
                      <span class="ml-2">{{ search }}</span>
                    </div>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Search Results -->
            <div v-else-if="hasResults" class="max-h-80 scroll-py-2 overflow-y-auto">
              <div v-for="(group, key) in groupedResults" :key="key" class="p-2">
                <h2 class="mb-2 px-3 text-xs font-semibold text-gray-500">
                  {{ group.label }}
                </h2>
                <ul>
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
            <div v-else-if="searchableResources.length > 0" class="max-h-80 scroll-py-2 overflow-y-auto">
              <div class="p-2">
                <h2 class="px-3 py-2 text-xs font-semibold text-gray-500">
                  Search in...
                </h2>
                <ul class="grid grid-cols-2 gap-1">
                  <li
                    v-for="resource in searchableResources"
                    :key="resource.key"
                    class="flex items-center rounded-md px-3 py-2 text-sm text-gray-700"
                  >
                    <span
                      v-if="resource.icon"
                      class="mr-2 h-4 w-4 text-gray-400"
                      v-html="resource.icon"
                    ></span>
                    <span>{{ resource.label }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex flex-wrap items-center bg-gray-50 px-4 py-2.5 text-xs text-gray-700">
              <kbd class="mr-1 rounded border border-gray-200 bg-white px-1.5 py-0.5 font-medium">↵</kbd>
              <span class="mr-3">to select</span>
              <kbd class="mr-1 rounded border border-gray-200 bg-white px-1.5 py-0.5 font-medium">↑↓</kbd>
              <span class="mr-3">to navigate</span>
              <kbd class="mr-1 rounded border border-gray-200 bg-white px-1.5 py-0.5 font-medium">esc</kbd>
              <span>to close</span>
            </div>
          </div>
        </Transition>
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
  }
})

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
  open,
  close,
  clearRecentSearches,
  selectRecentSearch,
  selectResult,
  handleKeydown,
  getShortcutDisplay
} = useGlobalSearch({ panel: props.panel })

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
  open,
  close
})
</script>
