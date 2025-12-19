import { computed } from 'vue'
import { useRoute } from 'vue-router'

/**
 * Composable to get context-specific routes based on current panel (admin/user)
 * This ensures profile and other common pages use the correct panel-specific routes
 *
 * @param {Object} options - Configuration options
 * @param {Function} options.isAdminFn - Optional function to determine if user is admin
 * @param {Object} options.adminRoutes - Custom admin route names
 * @param {Object} options.userRoutes - Custom user route names
 * @returns {Object} Context routes
 */
export function useContextRoutes(options = {}) {
  const route = useRoute()

  const {
    isAdminFn = null,
    adminRoutes = {},
    userRoutes = {}
  } = options

  // Default admin route names
  const defaultAdminRoutes = {
    dashboard: 'admin.dashboard',
    profile: {
      personal: 'admin.profile.personal',
      security: 'admin.profile.security',
    },
    settings: {
      appearance: 'admin.settings.appearance',
      notifications: 'admin.settings.notifications',
      preferences: 'admin.settings.preferences',
      global: 'admin.settings.global',
      system: 'admin.settings.system',
    },
    errors: {
      notFound: 'admin.error.notFound',
      forbidden: 'admin.error.forbidden',
      unauthorized: 'admin.error.unauthorized',
      serverError: 'admin.error.serverError',
      networkError: 'admin.error.networkError',
      maintenance: 'admin.error.maintenance',
    }
  }

  // Default user/panel route names
  const defaultUserRoutes = {
    dashboard: 'panel.dashboard',
    profile: {
      personal: 'panel.profile.personal',
      security: 'panel.profile.security',
    },
    settings: {
      appearance: 'panel.settings.appearance',
      notifications: 'panel.settings.notifications',
      preferences: 'panel.settings.preferences',
    },
    errors: {
      notFound: 'panel.error.notFound',
      forbidden: 'panel.error.forbidden',
      unauthorized: 'panel.error.forbidden',
      serverError: 'panel.error.notFound',
      networkError: 'panel.error.notFound',
      maintenance: 'panel.error.notFound',
    }
  }

  // Merge with custom routes
  const mergedAdminRoutes = { ...defaultAdminRoutes, ...adminRoutes }
  const mergedUserRoutes = { ...defaultUserRoutes, ...userRoutes }

  // Determine if we're in admin context
  const isAdminContext = computed(() => {
    if (isAdminFn) {
      return isAdminFn()
    }
    return route?.path?.startsWith('/admin')
  })

  // Get profile routes based on context
  const profileRoutes = computed(() => {
    if (isAdminContext.value) {
      return mergedAdminRoutes.profile
    } else {
      return mergedUserRoutes.profile
    }
  })

  // Get dashboard route based on context
  const dashboardRoute = computed(() => {
    if (isAdminContext.value) {
      return { name: mergedAdminRoutes.dashboard }
    } else {
      return { name: mergedUserRoutes.dashboard }
    }
  })

  // Get settings routes based on context
  const settingsRoutes = computed(() => {
    if (isAdminContext.value) {
      return mergedAdminRoutes.settings
    } else {
      return mergedUserRoutes.settings
    }
  })

  // Get error routes based on context
  const errorRoutes = computed(() => {
    if (isAdminContext.value) {
      return mergedAdminRoutes.errors
    } else {
      return mergedUserRoutes.errors
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
