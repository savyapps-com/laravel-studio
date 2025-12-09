<template>
  <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30 transition-colors duration-300">
    <div class="px-4 py-3 sm:px-6">
      <div class="flex items-center justify-between">
        <!-- Left Section -->
        <div class="flex items-center space-x-4">
          <!-- Desktop sidebar toggle -->
          <button
            @click="$emit('toggle-sidebar')"
            class="hidden lg:flex btn-ghost"
            aria-label="Toggle sidebar"
          >
            <Icon name="menu" :size="20" />
          </button>

          <!-- Mobile menu button -->
          <button
            @click="$emit('toggle-mobile-sidebar')"
            class="lg:hidden btn-ghost"
            aria-label="Toggle sidebar"
          >
            <Icon name="menu" :size="24" />
          </button>

          <!-- Page Name -->
          <div class="hidden sm:flex items-center">
            <span class="text-lg font-semibold capitalize text-title">
              {{ pageName }}
            </span>
          </div>
        </div>

        <!-- Right Section -->
        <div class="flex items-center space-x-2 sm:space-x-4">
          <!-- Global Search (Cmd+K) -->
          <div class="hidden md:block">
            <button
              @click="openSearch"
              class="flex items-center gap-2 px-3 py-2 text-sm text-muted bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
            >
              <Icon name="search" :size="16" />
              <span class="hidden xl:inline">Search...</span>
              <kbd class="hidden xl:inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-600 rounded">
                <span class="text-xs">⌘</span>K
              </kbd>
            </button>
          </div>

          <!-- Mobile Search Button -->
          <button
            @click="openSearch"
            class="md:hidden btn-ghost"
            aria-label="Search"
          >
            <Icon name="search" :size="20" />
          </button>

          <!-- Dark Mode Toggle -->
          <DarkModeToggle />

          <!-- Notifications -->
          <div class="relative">
            <button
              @click="toggleNotifications"
              class="btn-ghost"
              aria-label="View notifications"
            >
              <Icon name="bell" :size="24" />
              <span
                v-if="notificationCount > 0"
                class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 dark:bg-red-600 text-white text-xs font-bold rounded-full flex items-center justify-center"
              >
                {{ notificationCount > 9 ? '9+' : notificationCount }}
              </span>
            </button>

            <!-- Notification dropdown (placeholder) -->
            <div
              v-if="showNotifications"
              class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
              @click.stop
            >
              <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-title">Notifications</h3>
              </div>
              <div class="p-3 text-sm text-muted">
                {{ notificationCount > 0 ? `${notificationCount} new notifications` : 'No new notifications' }}
              </div>
            </div>
          </div>

          <!-- User Profile Dropdown -->
          <UserDropdown
            :user="user"
            @logout="$emit('logout')"
          />
        </div>
      </div>
    </div>

    <!-- Global Search Palette -->
    <SearchPalette
      v-model:open="searchOpen"
      @select="handleSearchSelect"
    />
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { SearchPalette } from '@core/index'
import Icon from '@/components/common/Icon.vue'
import UserDropdown from './UserDropdown.vue'
import DarkModeToggle from '@/components/common/DarkModeToggle.vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  notificationCount: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['toggle-sidebar', 'toggle-mobile-sidebar', 'logout'])

const route = useRoute()
const router = useRouter()
const searchOpen = ref(false)
const showNotifications = ref(false)

const pageName = computed(() => {
  return route.meta?.title || 'Page Name'
})

const openSearch = () => {
  searchOpen.value = true
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
}

const handleSearchSelect = (result) => {
  if (result.url) {
    router.push(result.url)
  }
  searchOpen.value = false
}

// Keyboard shortcut for Cmd+K / Ctrl+K
const handleKeydown = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    searchOpen.value = true
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
