<template>
  <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30 transition-colors duration-300">
    <div class="px-4 py-3 sm:px-6">
      <div class="flex items-center justify-between">
        <!-- Left Section -->
        <div class="flex items-center space-x-4">
          <!-- Desktop sidebar toggle -->
          <button
            v-if="showSidebarToggle"
            @click="$emit('toggle-sidebar')"
            class="hidden lg:flex btn-ghost"
            aria-label="Toggle sidebar"
          >
            <Icon name="menu" :size="20" />
          </button>

          <!-- Mobile menu button -->
          <button
            v-if="showMobileSidebarToggle"
            @click="$emit('toggle-mobile-sidebar')"
            class="lg:hidden btn-ghost"
            aria-label="Toggle sidebar"
          >
            <Icon name="menu" :size="24" />
          </button>

          <!-- Page Name -->
          <div v-if="showPageName" class="hidden sm:flex items-center">
            <span class="text-lg font-semibold capitalize text-title">
              {{ pageName }}
            </span>
          </div>
        </div>

        <!-- Right Section -->
        <div class="flex items-center space-x-2 sm:space-x-4">
          <!-- Global Search (Cmd+K) -->
          <div v-if="showSearch" class="hidden md:block">
            <button
              @click="openSearch"
              class="flex items-center gap-2 px-3 py-2 text-sm text-muted bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
            >
              <Icon name="search" :size="16" />
              <span class="hidden xl:inline">{{ searchPlaceholder }}</span>
              <kbd class="hidden xl:inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-600 rounded">
                <span class="text-xs">{{ isMac ? '⌘' : 'Ctrl+' }}</span>K
              </kbd>
            </button>
          </div>

          <!-- Mobile Search Button -->
          <button
            v-if="showSearch"
            @click="openSearch"
            class="md:hidden btn-ghost"
            aria-label="Search"
          >
            <Icon name="search" :size="20" />
          </button>

          <!-- Dark Mode Toggle -->
          <DarkModeToggle v-if="showDarkModeToggle" :on-save="onDarkModeSave" />

          <!-- Notifications -->
          <div v-if="showNotifications" class="relative">
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

            <!-- Notification dropdown -->
            <div
              v-if="showNotificationsDropdown"
              class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
              @click.stop
            >
              <slot name="notifications">
                <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                  <h3 class="text-sm font-semibold text-title">{{ notificationsTitle }}</h3>
                </div>
                <div class="p-3 text-sm text-muted">
                  {{ notificationCount > 0 ? `${notificationCount} new notifications` : 'No new notifications' }}
                </div>
              </slot>
            </div>
          </div>

          <!-- Custom actions slot -->
          <slot name="actions" />

          <!-- User Profile Dropdown -->
          <UserDropdown
            v-if="user"
            :user="user"
            :profile-route="profileRoute"
            :settings-route="settingsRoute"
            :profile-label="profileLabel"
            :settings-label="settingsLabel"
            :logout-label="logoutLabel"
            :custom-links="userMenuLinks"
            @logout="$emit('logout')"
          >
            <template #avatar>
              <slot name="user-avatar" />
            </template>
            <template #menu-items="slotProps">
              <slot name="user-menu-items" v-bind="slotProps" />
            </template>
          </UserDropdown>
        </div>
      </div>
    </div>

    <!-- Global Search Palette -->
    <SearchPalette
      v-if="showSearch"
      v-model:open="searchOpen"
      @select="handleSearchSelect"
    />
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SearchPalette from '../search/SearchPalette.vue'
import Icon from '../common/Icon.vue'
import UserDropdown from './UserDropdown.vue'
import DarkModeToggle from './DarkModeToggle.vue'

const props = defineProps({
  user: {
    type: Object,
    default: null
  },
  notificationCount: {
    type: Number,
    default: 0
  },
  showSidebarToggle: {
    type: Boolean,
    default: true
  },
  showMobileSidebarToggle: {
    type: Boolean,
    default: true
  },
  showPageName: {
    type: Boolean,
    default: true
  },
  showSearch: {
    type: Boolean,
    default: true
  },
  showDarkModeToggle: {
    type: Boolean,
    default: true
  },
  showNotifications: {
    type: Boolean,
    default: true
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  notificationsTitle: {
    type: String,
    default: 'Notifications'
  },
  profileRoute: {
    type: [String, Object],
    default: null
  },
  settingsRoute: {
    type: [String, Object],
    default: null
  },
  profileLabel: {
    type: String,
    default: 'Profile'
  },
  settingsLabel: {
    type: String,
    default: 'Settings'
  },
  logoutLabel: {
    type: String,
    default: 'Sign out'
  },
  userMenuLinks: {
    type: Array,
    default: () => []
  },
  onDarkModeSave: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['toggle-sidebar', 'toggle-mobile-sidebar', 'logout', 'search-select', 'notification-click'])

const route = useRoute()
const router = useRouter()
const searchOpen = ref(false)
const showNotificationsDropdown = ref(false)

// Detect if Mac for keyboard shortcut display
const isMac = computed(() => {
  if (typeof navigator !== 'undefined') {
    return navigator.platform.toUpperCase().indexOf('MAC') >= 0
  }
  return false
})

const pageName = computed(() => {
  return route.meta?.title || 'Page'
})

const openSearch = () => {
  searchOpen.value = true
}

const toggleNotifications = () => {
  showNotificationsDropdown.value = !showNotificationsDropdown.value
  emit('notification-click')
}

const handleSearchSelect = (result) => {
  if (result.url) {
    router.push(result.url)
  }
  searchOpen.value = false
  emit('search-select', result)
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
