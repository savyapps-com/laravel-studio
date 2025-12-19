import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@core/stores/auth'

/**
 * Composable to get context-specific routes based on current panel (admin/user)
 * This ensures profile and other common pages use the correct panel-specific routes
 */
export function useContextRoutes() {
  const route = useRoute()
  const authStore = useAuthStore()

  // Determine if we're in admin context
  const isAdminContext = computed(() => {
    return route?.path?.startsWith('/admin') || authStore.user?.can_access_admin_panel
  })

  // Get profile routes based on context
  const profileRoutes = computed(() => {
    if (isAdminContext.value) {
      return {
        personal: 'admin.profile.personal',
        security: 'admin.profile.security',
      }
    } else {
      return {
        personal: 'panel.profile.personal',
        security: 'panel.profile.security',
      }
    }
  })

  // Get dashboard route based on context
  const dashboardRoute = computed(() => {
    if (isAdminContext.value) {
      return { name: 'admin.dashboard' }
    } else {
      return { name: 'panel.dashboard' }
    }
  })

  // Get settings routes based on context
  const settingsRoutes = computed(() => {
    if (isAdminContext.value) {
      return {
        appearance: 'admin.settings.appearance',
        notifications: 'admin.settings.notifications',
        preferences: 'admin.settings.preferences',
        global: 'admin.settings.global',
        system: 'admin.settings.system',
      }
    } else {
      return {
        appearance: 'panel.settings.appearance',
        notifications: 'panel.settings.notifications',
        preferences: 'panel.settings.preferences',
      }
    }
  })

  // Get error routes based on context
  const errorRoutes = computed(() => {
    if (isAdminContext.value) {
      return {
        notFound: 'admin.error.notFound',
        forbidden: 'admin.error.forbidden',
        unauthorized: 'admin.error.unauthorized',
        serverError: 'admin.error.serverError',
        networkError: 'admin.error.networkError',
        maintenance: 'admin.error.maintenance',
      }
    } else {
      return {
        notFound: 'panel.error.notFound',
        forbidden: 'panel.error.forbidden',
        unauthorized: 'panel.error.forbidden',
        serverError: 'panel.error.notFound',
        networkError: 'panel.error.notFound',
        maintenance: 'panel.error.notFound',
      }
    }
  })

  return {
    isAdminContext,
    profileRoutes,
    settingsRoutes,
    dashboardRoute,
    errorRoutes,
  }
}
