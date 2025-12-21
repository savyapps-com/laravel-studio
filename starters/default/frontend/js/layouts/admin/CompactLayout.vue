<template>
  <div class="page-container font-sans">
    <!-- Compact Sidebar (always icon-only, 64px width) -->
    <CompactSidebar
      :menu-items="allMenuItems"
      @nav-click="handleNavClick"
      @logout="logout"
    />

    <!-- Main Content -->
    <div class="content-area pl-16">
      <!-- Impersonation Banner -->
      <ImpersonationBanner
        ref="impersonationBanner"
        :is-impersonating="isImpersonating"
        :user-name="impersonationStatus?.user?.name || ''"
        :user-email="impersonationStatus?.user?.email || ''"
        :admin-name="impersonationStatus?.admin?.name || ''"
      />

      <!-- Top Navbar -->
      <Navbar
        :user="user"
        :notification-count="notificationCount"
        :hide-toggle="true"
        :profile-route="profileRoute"
        :settings-route="settingsRoute"
        @logout="logout"
        @search="handleSearch"
      />

      <!-- Main Content Area -->
      <main class="p-4 sm:p-5 lg:p-6">
        <router-view />
      </main>
    </div>

    <!-- Confirmation Dialog Container -->
    <ConfirmDialogContainer />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  CompactSidebar,
  Navbar,
  ImpersonationBanner,
  ConfirmDialogContainer,
  useAuthStore
} from 'laravel-studio'
import { adminMainMenuItems, getAdminMoreMenuItems, userMainMenuItems, getUserMoreMenuItems } from '@/config/menuItems'
import { useContextRoutes } from 'laravel-studio'

export default {
  name: 'CompactLayout',
  components: {
    CompactSidebar,
    Navbar,
    ImpersonationBanner,
    ConfirmDialogContainer,
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const { profileRoutes, settingsRoutes } = useContextRoutes()

    // Determine menu items based on current route
    const currentPath = computed(() => router.currentRoute.value.path)
    const isAdminPanel = computed(() => currentPath.value.startsWith('/admin'))

    // Combine main and more menu items for compact sidebar
    const allMenuItems = computed(() => {
      const main = isAdminPanel.value ? adminMainMenuItems : userMainMenuItems
      const more = isAdminPanel.value ? getAdminMoreMenuItems() : getUserMoreMenuItems()
      return [...main, ...more]
    })

    const notificationCount = ref(3)

    // Get user from auth store
    const user = computed(() => authStore.user ? {
      name: authStore.user.name,
      role: authStore.user.role_name || 'User',
      initials: authStore.user.name.charAt(0).toUpperCase()
    } : {
      name: 'User',
      role: 'User',
      initials: 'U'
    })

    // Profile and Settings routes
    const profileRoute = computed(() => ({ name: profileRoutes.value.personal }))
    const settingsRoute = computed(() => ({ name: settingsRoutes.value.appearance }))

    // Impersonation
    const isImpersonating = computed(() => !!authStore.impersonationStatus?.user)

    // Methods
    const logout = async () => {
      try {
        await authStore.logout()
        router.push({ name: 'panel.login', params: { panel: 'admin' } })
      } catch (error) {
        console.error('Logout error:', error)
        router.push({ name: 'panel.login', params: { panel: 'admin' } })
      }
    }

    const handleSearch = (query) => {
      console.log('Search query:', query)
    }

    const handleNavClick = () => {
      // Handle navigation click if needed
    }

    // Fetch user data on mount if not already loaded
    onMounted(async () => {
      if (!authStore.user && authStore.token) {
        try {
          await authStore.fetchUser()
        } catch (error) {
          console.error('Failed to fetch user:', error)
        }
      }
    })

    return {
      allMenuItems,
      notificationCount,
      user,
      profileRoute,
      settingsRoute,
      isImpersonating,
      impersonationStatus: computed(() => authStore.impersonationStatus),
      logout,
      handleSearch,
      handleNavClick,
    }
  }
}
</script>
