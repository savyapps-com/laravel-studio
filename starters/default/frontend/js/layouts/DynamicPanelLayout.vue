<template>
  <div class="page-container font-sans">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ error }}</h1>
        <router-link :to="{ name: 'panel.login', params: { panel: currentPanel || 'admin' } }" class="btn btn-primary">
          Go to Login
        </router-link>
      </div>
    </div>

    <!-- Panel Content -->
    <template v-else>
      <!-- Mobile Overlay -->
      <div
        v-if="isMobileSidebarOpen"
        @click="closeMobileSidebar"
        class="mobile-overlay"
        :class="{ 'opacity-100': isMobileSidebarOpen, 'opacity-0': !isMobileSidebarOpen }"
      ></div>

      <!-- Sidebar -->
      <Sidebar
        :collapsed="isDesktopSidebarCollapsed"
        :is-open="isMobileSidebarOpen"
        :is-mobile="isMobile"
        :message-count="0"
        :logo-title="panelLabel"
        :main-menu-items="menuItems"
        :more-menu-items="moreMenuItems"
        @close="closeMobileSidebar"
        @nav-click="closeMobileSidebarOnNavigation"
        @logout="logout"
      />

      <!-- Main Content -->
      <div class="content-area" :class="mainContentClasses">
        <!-- Impersonation Banner -->
        <ImpersonationBanner ref="impersonationBanner" />

        <!-- Top Navbar -->
        <Navbar
          :user="user"
          :notification-count="0"
          @toggle-sidebar="toggleDesktopSidebar"
          @toggle-mobile-sidebar="toggleMobileSidebar"
          @logout="logout"
          @search="handleSearch"
        />

        <!-- Main Content Area -->
        <main class="p-4 sm:p-5 lg:p-6 content-area">
          <router-view />
        </main>
      </div>

      <!-- Confirmation Dialog Container -->
      <ConfirmDialogContainer />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  Sidebar,
  Navbar,
  ImpersonationBanner,
  ConfirmDialogContainer,
  useSidebar,
  useEscapeKey,
  useAuthStore,
  panelService
} from 'laravel-studio'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// Panel state
const isLoading = ref(true)
const error = ref(null)
const panelConfig = ref(null)

// Get current panel from route
const currentPanel = computed(() => route.params.panel)

// Panel label
const panelLabel = computed(() => panelConfig.value?.label || currentPanel.value?.charAt(0).toUpperCase() + currentPanel.value?.slice(1) || 'Panel')

// Convert API menu format to component menu format
const menuItems = computed(() => {
  if (!panelConfig.value?.menu) return []

  return panelConfig.value.menu
    .map(item => {
      // Handle header items
      if (item.type === 'header') {
        return { type: 'heading', text: item.label || '' }
      }

      // Handle divider items
      if (item.type === 'divider') {
        return { type: 'separator' }
      }

      // Handle link items
      if (item.type === 'link') {
        let to = null

        // Resource items have a 'resource' property
        if (item.resource) {
          to = { name: 'panel.resource', params: { panel: currentPanel.value, resource: item.resource } }
        }
        // Feature items or regular links with route
        else if (typeof item.route === 'string') {
          // Check if this is an API route (e.g., 'api.panels.customer.resources.users.index')
          if (item.route.includes('api.panels')) {
            // This is a resource route from backend, extract resource name
            const match = item.route.match(/resources\.([^.]+)/)
            if (match) {
              to = { name: 'panel.resource', params: { panel: currentPanel.value, resource: match[1] } }
            } else {
              to = { name: 'panel.dashboard', params: { panel: currentPanel.value } }
            }
          } else {
            // Extract the route suffix (e.g., 'dashboard' from 'customer.dashboard')
            const routeParts = item.route.split('.')
            const routeSuffix = routeParts[routeParts.length - 1]

            // Map common route suffixes to panel.* routes
            const routeMap = {
              'dashboard': 'panel.dashboard',
              'profile': 'panel.profile.personal',
              'settings': 'panel.settings.appearance'
            }

            if (routeMap[routeSuffix]) {
              to = { name: routeMap[routeSuffix], params: { panel: currentPanel.value } }
            } else {
              // For other routes, use the panel path directly
              to = `/${currentPanel.value}/${routeSuffix}`
            }
          }
        }

        // Only return if we have valid required properties
        if (to && item.label) {
          return {
            type: 'link',
            label: item.label,
            icon: item.icon || 'circle',
            to
          }
        }
        return null
      }

      // Skip unknown item types
      return null
    })
    .filter(item => item !== null)
})

// More menu items for user actions
const moreMenuItems = computed(() => [
  {
    type: 'link',
    label: 'Profile',
    icon: 'user',
    to: { name: 'panel.profile.personal', params: { panel: currentPanel.value } }
  },
  {
    type: 'link',
    label: 'Settings',
    icon: 'settings',
    to: { name: 'panel.settings.appearance', params: { panel: currentPanel.value } }
  }
])

// Sidebar composable
const {
  isMobileSidebarOpen,
  isDesktopSidebarCollapsed,
  isMobile,
  mainContentClasses,
  toggleDesktopSidebar,
  toggleMobileSidebar,
  closeMobileSidebar,
  closeMobileSidebarOnNavigation,
} = useSidebar()

// User computed
const user = computed(() => authStore.user ? {
  name: authStore.user.name,
  role: authStore.user.role_name || 'User',
  initials: authStore.user.name.charAt(0).toUpperCase()
} : {
  name: 'User',
  role: 'User',
  initials: 'U'
})

// Load panel configuration
async function loadPanelConfig() {
  if (!currentPanel.value) {
    error.value = 'No panel specified'
    isLoading.value = false
    return
  }

  isLoading.value = true
  error.value = null

  try {
    const config = await panelService.getPanelConfig(currentPanel.value)
    panelConfig.value = config
  } catch (err) {
    console.error('Failed to load panel config:', err)
    if (err.response?.status === 403) {
      error.value = 'You do not have access to this panel'
    } else if (err.response?.status === 404) {
      error.value = 'Panel not found'
    } else {
      error.value = 'Failed to load panel configuration'
    }
  } finally {
    isLoading.value = false
  }
}

// Watch for panel changes
watch(currentPanel, () => {
  loadPanelConfig()
}, { immediate: true })

// Logout handler
async function logout() {
  try {
    await authStore.logout()
    router.push({ name: 'panel.login', params: { panel: currentPanel.value || 'admin' } })
  } catch (err) {
    console.error('Logout error:', err)
    router.push({ name: 'panel.login', params: { panel: 'admin' } })
  }
}

// Search handler
function handleSearch(query) {
  console.log('Search query:', query)
}

// Escape key to close mobile sidebar
useEscapeKey(() => {
  if (isMobileSidebarOpen.value) {
    closeMobileSidebar()
  }
})
</script>

<style scoped>
@supports (backdrop-filter: blur(8px)) {
  .mobile-overlay {
    backdrop-filter: blur(8px);
  }
}
</style>
