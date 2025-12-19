import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@core/stores/auth'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DynamicPanelLayout from '@/layouts/DynamicPanelLayout.vue'
import ProfileLayout from '@/layouts/ProfileLayout.vue'
import SettingsLayout from '@/layouts/SettingsLayout.vue'
import Dashboard from '@/pages/Dashboard.vue'

const routes = [
  // Panel-scoped Auth Routes (guest routes under each panel)
  // Auth pages are provided by the laravel-studio package
  {
    path: '/:panel/login',
    name: 'panel.login',
    component: () => import('@core/pages/auth/Login.vue'),
    meta: { title: 'Sign In', guest: true }
  },
  {
    path: '/:panel/register',
    name: 'panel.register',
    component: () => import('@core/pages/auth/Register.vue'),
    meta: { title: 'Create Account', guest: true }
  },
  {
    path: '/:panel/forgot-password',
    name: 'panel.forgot-password',
    component: () => import('@core/pages/auth/ForgotPassword.vue'),
    meta: { title: 'Reset Password', guest: true }
  },
  {
    path: '/:panel/reset-password',
    name: 'panel.reset-password',
    component: () => import('@core/pages/auth/ResetPassword.vue'),
    meta: { title: 'Set New Password', guest: true }
  },
  {
    path: '/:panel/verify-email',
    name: 'panel.verify-email',
    component: () => import('@core/pages/auth/VerifyEmail.vue'),
    meta: { title: 'Verify Email', guest: true }
  },

  // Admin Panel Routes
  {
    path: '/admin',
    component: AdminLayout,
    meta: { auth: 'admin' },
    children: [
      { path: '', name: 'admin.dashboard', component: Dashboard, meta: { title: 'Dashboard', auth: 'admin' } },
      { path: 'users', name: 'admin.users', component: () => import('@/pages/admin/UsersResource.vue'), meta: { title: 'Users', auth: 'admin' } },
      { path: 'roles', name: 'admin.roles', component: () => import('@/pages/admin/RolesResource.vue'), meta: { title: 'Roles', auth: 'admin' } },
      { path: 'activity', name: 'admin.activity', component: () => import('@/pages/admin/Activity.vue'), meta: { title: 'Activity Log', auth: 'admin' } },
      { path: 'email-templates', name: 'admin.email-templates.index', component: () => import('@/pages/admin/EmailTemplates.vue'), meta: { title: 'Email Templates', auth: 'admin' } },
      { path: 'email-templates/create', name: 'admin.email-templates.create', component: () => import('@/pages/admin/EmailTemplateForm.vue'), meta: { title: 'Create Email Template', auth: 'admin' } },
      { path: 'email-templates/:id/edit', name: 'admin.email-templates.edit', component: () => import('@/pages/admin/EmailTemplateForm.vue'), meta: { title: 'Edit Email Template', auth: 'admin' } },

      // Admin Profile Routes
      {
        path: 'profile',
        component: ProfileLayout,
        meta: { auth: 'admin' },
        redirect: { name: 'admin.profile.personal' },
        children: [
          { path: 'personal', name: 'admin.profile.personal', component: () => import('@/pages/admin/profile/Profile.vue'), meta: { title: 'Profile', auth: 'admin' } },
          { path: 'security', name: 'admin.profile.security', component: () => import('@/pages/admin/profile/ChangePassword.vue'), meta: { title: 'Security', auth: 'admin' } }
        ]
      },

      // Admin Settings Routes
      {
        path: 'settings',
        name: 'admin.settings',
        component: SettingsLayout,
        meta: { auth: 'admin' },
        redirect: { name: 'admin.settings.appearance' },
        children: [
          { path: 'appearance', name: 'admin.settings.appearance', component: () => import('@/pages/admin/settings/Appearance.vue'), meta: { title: 'Appearance', auth: 'admin' } },
          { path: 'notifications', name: 'admin.settings.notifications', component: () => import('@/pages/admin/settings/Notifications.vue'), meta: { title: 'Notifications', auth: 'admin' } },
          { path: 'preferences', name: 'admin.settings.preferences', component: () => import('@/pages/admin/settings/Preferences.vue'), meta: { title: 'Preferences', auth: 'admin' } },
          { path: 'global', name: 'admin.settings.global', component: () => import('@/pages/settings/Global.vue'), meta: { title: 'Global Settings', auth: 'admin' } },
          { path: 'system', name: 'admin.settings.system', component: () => import('@/pages/settings/System.vue'), meta: { title: 'System Settings', auth: 'admin' } }
        ]
      },

      // Admin Error Pages
      { path: 'error/404', name: 'admin.error.notFound', component: () => import('@/pages/admin/errors/NotFound.vue'), meta: { title: '404 - Page Not Found', auth: '' } },
      { path: 'error/403', name: 'admin.error.forbidden', component: () => import('@/pages/admin/errors/Forbidden.vue'), meta: { title: '403 - Access Forbidden', auth: '' } },
      { path: 'error/401', name: 'admin.error.unauthorized', component: () => import('@/pages/admin/errors/Unauthorized.vue'), meta: { title: '401 - Unauthorized', auth: '' } },
      { path: 'error/500', name: 'admin.error.serverError', component: () => import('@/pages/admin/errors/ServerError.vue'), meta: { title: '500 - Server Error', auth: '' } },
      { path: 'error/network', name: 'admin.error.networkError', component: () => import('@/pages/admin/errors/NetworkError.vue'), meta: { title: 'Network Error', auth: '' } },
      { path: 'error/maintenance', name: 'admin.error.maintenance', component: () => import('@/pages/admin/errors/MaintenanceMode.vue'), meta: { title: 'Under Maintenance', auth: '' } }
    ]
  },

  // Root route - redirect to appropriate dashboard based on user's default panel
  {
    path: '/',
    name: 'home',
    redirect: () => {
      const authStore = useAuthStore()
      if (authStore.isAuthenticated) {
        // Get user's default panel from their accessible panels
        const defaultPanel = authStore.user?.default_panel || 'admin'
        // Admin has its own named route, other panels use the dynamic route
        if (defaultPanel === 'admin') {
          return { name: 'admin.dashboard' }
        }
        return { name: 'panel.dashboard', params: { panel: defaultPanel } }
      }
      return { name: 'panel.login', params: { panel: 'admin' } }
    }
  },

  // Dynamic Panel Routes (for panels other than admin)
  // These routes handle any panel created via the admin panel management
  {
    path: '/:panel',
    component: DynamicPanelLayout,
    meta: { auth: 'panel', dynamic: true },
    beforeEnter: (to, from, next) => {
      // Redirect 'admin' panel to the explicit admin routes
      if (to.params.panel === 'admin') {
        next({ name: 'admin.dashboard', replace: true })
      } else {
        next()
      }
    },
    children: [
      // Panel Dashboard
      {
        path: '',
        name: 'panel.dashboard',
        component: () => import('@/pages/panel/DynamicDashboard.vue'),
        meta: { title: 'Dashboard', auth: 'panel' }
      },
      // Dynamic Resource Routes
      {
        path: 'resources/:resource',
        name: 'panel.resource',
        component: () => import('@/pages/panel/DynamicResource.vue'),
        meta: { title: 'Resource', auth: 'panel' }
      },
      // Panel Profile
      {
        path: 'profile',
        component: ProfileLayout,
        meta: { auth: 'panel' },
        redirect: { name: 'panel.profile.personal' },
        children: [
          { path: 'personal', name: 'panel.profile.personal', component: () => import('@/pages/admin/profile/Profile.vue'), meta: { title: 'Profile', auth: 'panel' } },
          { path: 'security', name: 'panel.profile.security', component: () => import('@/pages/admin/profile/ChangePassword.vue'), meta: { title: 'Security', auth: 'panel' } }
        ]
      },
      // Panel Settings
      {
        path: 'settings',
        name: 'panel.settings',
        component: SettingsLayout,
        meta: { auth: 'panel' },
        redirect: { name: 'panel.settings.appearance' },
        children: [
          { path: 'appearance', name: 'panel.settings.appearance', component: () => import('@/pages/admin/settings/Appearance.vue'), meta: { title: 'Appearance', auth: 'panel' } },
          { path: 'notifications', name: 'panel.settings.notifications', component: () => import('@/pages/admin/settings/Notifications.vue'), meta: { title: 'Notifications', auth: 'panel' } },
          { path: 'preferences', name: 'panel.settings.preferences', component: () => import('@/pages/admin/settings/Preferences.vue'), meta: { title: 'Preferences', auth: 'panel' } }
        ]
      },
      // Panel Error Pages
      {
        path: 'error/404',
        name: 'panel.error.notFound',
        component: () => import('@/pages/admin/errors/NotFound.vue'),
        meta: { title: '404 - Page Not Found', auth: '' }
      },
      {
        path: 'error/403',
        name: 'panel.error.forbidden',
        component: () => import('@/pages/admin/errors/Forbidden.vue'),
        meta: { title: '403 - Access Forbidden', auth: '' }
      }
    ]
  },

  // 404 Catch-all for all other paths
  {
    path: '/:pathMatch(.*)*',
    name: 'notFound',
    redirect: () => {
      const authStore = useAuthStore()
      if (authStore.isAuthenticated) {
        return { name: 'admin.error.notFound' }
      }
      return { name: 'panel.login', params: { panel: 'admin' } }
    }
  }
]

const router = createRouter({ history: createWebHistory(), routes })

// Navigation guards
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()

  // Get panel from route params or path
  const panel = to.params.panel || to.path.match(/^\/([a-z]+)/)?.[1] || 'admin'

  // Update document title
  if (to.meta.title) {
    const suffix = panel.charAt(0).toUpperCase() + panel.slice(1) + ' Panel'
    document.title = `${to.meta.title} - ${suffix}`
  }

  // Check if route requires authentication (auth meta is set)
  if (to.meta.auth !== undefined) {
    // Ensure user is authenticated for all protected routes
    if (!authStore.isAuthenticated) {
      // Only store redirect if it's not a 404 catch-all route
      const isValidRoute = to.matched.length > 0 &&
        !to.name?.toString().includes('notFound') &&
        !to.name?.toString().includes('NotFound')

      const query = isValidRoute ? { redirect: to.fullPath } : {}
      // Redirect to panel-scoped login
      next({ name: 'panel.login', params: { panel }, query })
      return
    }

    // Check role-based access
    const requiredAuth = to.meta.auth

    if (requiredAuth === 'admin') {
      // Admin-only route - check if user can access admin panel
      if (!authStore.user?.can_access_admin_panel) {
        next({ name: 'admin.error.forbidden' })
        return
      }
    } else if (requiredAuth === 'panel') {
      // Dynamic panel route - panel access is checked by the backend API
      // when loading panel config. The DynamicPanelLayout will handle
      // showing access denied errors. Just ensure user is authenticated.
      // If the panel doesn't exist or user can't access it, the layout will handle it.
    }
    // For requiredAuth === '' (common routes), user is authenticated and any role can access

    next()
  }
  // Check if route is guest only (login, register, etc.)
  else if (to.meta.guest) {
    if (authStore.isAuthenticated) {
      // Check if navigation was triggered by user action from an existing route
      if (_from.name && !_from.meta?.guest) {
        // Coming from an authenticated route (like dashboard), cancel navigation
        next(false)
      } else {
        // Direct navigation (new tab/refresh) or from another guest route, redirect to panel dashboard
        const defaultPanel = authStore.user?.default_panel || panel
        // Use query redirect if available, otherwise go to dashboard
        if (to.query.redirect) {
          next(to.query.redirect)
        } else if (defaultPanel === 'admin') {
          next({ name: 'admin.dashboard' })
        } else {
          next({ name: 'panel.dashboard', params: { panel: defaultPanel } })
        }
      }
    } else {
      next()
    }
  }
  // Public route (no auth meta)
  else {
    next()
  }
})

export default router
