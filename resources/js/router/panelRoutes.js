/**
 * Panel Routes Generator
 * Generates Vue Router routes dynamically based on panel configuration
 */

import { panelService } from '../services/panelService'

/**
 * Generate routes for a single panel
 * @param {Object} panelConfig - Panel configuration object
 * @param {Object} options - Options for route generation
 * @returns {Object} Vue Router route configuration
 */
export function generatePanelRoutes(panelConfig, options = {}) {
  const {
    key,
    path,
    label,
    resources = [],
    features = []
  } = panelConfig

  const {
    // Default layout component - should be overridden by consuming app
    layoutComponent = () => import('../components/layout/PanelLayout.vue'),
    // Component for dashboard - should be overridden by consuming app
    dashboardComponent = null,
    // Component for resource index
    resourceIndexComponent = null,
    // Component for resource create
    resourceCreateComponent = null,
    // Component for resource show
    resourceShowComponent = null,
    // Component for resource edit
    resourceEditComponent = null,
    // Common routes to include in all panels
    commonRoutes = []
  } = options

  const children = [
    // Dashboard route
    {
      path: '',
      name: `${key}.dashboard`,
      component: dashboardComponent,
      meta: {
        panel: key,
        title: `${label} Dashboard`
      }
    },

    // Dynamic resource routes
    ...resources.flatMap(resource => {
      const resourceRoutes = []

      // Resource Index
      if (resourceIndexComponent) {
        resourceRoutes.push({
          path: resource,
          name: `${key}.resources.${resource}.index`,
          component: resourceIndexComponent,
          props: { resource, panel: key },
          meta: {
            panel: key,
            resource,
            action: 'index'
          }
        })
      }

      // Resource Create
      if (resourceCreateComponent) {
        resourceRoutes.push({
          path: `${resource}/create`,
          name: `${key}.resources.${resource}.create`,
          component: resourceCreateComponent,
          props: { resource, panel: key },
          meta: {
            panel: key,
            resource,
            action: 'create'
          }
        })
      }

      // Resource Show
      if (resourceShowComponent) {
        resourceRoutes.push({
          path: `${resource}/:id`,
          name: `${key}.resources.${resource}.show`,
          component: resourceShowComponent,
          props: route => ({
            resource,
            panel: key,
            id: route.params.id
          }),
          meta: {
            panel: key,
            resource,
            action: 'show'
          }
        })
      }

      // Resource Edit
      if (resourceEditComponent) {
        resourceRoutes.push({
          path: `${resource}/:id/edit`,
          name: `${key}.resources.${resource}.edit`,
          component: resourceEditComponent,
          props: route => ({
            resource,
            panel: key,
            id: route.params.id
          }),
          meta: {
            panel: key,
            resource,
            action: 'edit'
          }
        })
      }

      return resourceRoutes
    }),

    // Include common routes
    ...commonRoutes.map(route => ({
      ...route,
      name: `${key}.${route.name}`,
      meta: {
        ...route.meta,
        panel: key
      }
    }))
  ]

  return {
    path: path,
    component: layoutComponent,
    meta: {
      requiresAuth: true,
      panel: key
    },
    children: children.filter(c => c.component) // Filter out routes without components
  }
}

/**
 * Load panels from API and generate routes dynamically
 * @param {Object} router - Vue Router instance
 * @param {Object} options - Options for route generation
 * @returns {Promise<Array>} Generated routes
 */
export async function loadDynamicPanelRoutes(router, options = {}) {
  try {
    const response = await panelService.getAccessiblePanels()
    const { panels } = response

    const routes = panels.map(panel =>
      generatePanelRoutes(panel, options)
    )

    // Add routes to router
    routes.forEach(route => {
      router.addRoute(route)
    })

    return routes
  } catch (error) {
    console.error('Failed to load panel routes:', error)
    return []
  }
}

/**
 * Create panel route guard
 * Checks if user can access the panel
 * @param {Object} panelStore - Panel store instance
 * @returns {Function} Navigation guard
 */
export function createPanelGuard(panelStore) {
  return async (to, from, next) => {
    const panel = to.meta?.panel

    if (!panel) {
      return next()
    }

    // Initialize panel store if needed
    if (!panelStore.isInitialized) {
      await panelStore.initialize()
    }

    // Check if user has access to this panel
    const hasAccess = panelStore.accessiblePanels.some(p => p.key === panel)

    if (!hasAccess) {
      // Redirect to default panel or login
      const defaultPanel = panelStore.defaultPanel
      if (defaultPanel) {
        const defaultPanelConfig = panelStore.accessiblePanels.find(
          p => p.key === defaultPanel
        )
        if (defaultPanelConfig) {
          return next(defaultPanelConfig.path)
        }
      }
      return next({ name: 'login' })
    }

    // Load panel config if not loaded
    if (panelStore.currentPanel !== panel) {
      await panelStore.loadPanelConfig(panel)
    }

    return next()
  }
}

/**
 * Generate static panel routes from config
 * Use this when you have panel configuration available at build time
 * @param {Array} panelsConfig - Array of panel configurations
 * @param {Object} options - Options for route generation
 * @returns {Array} Array of Vue Router route configurations
 */
export function generateStaticPanelRoutes(panelsConfig, options = {}) {
  return panelsConfig.map(panel => generatePanelRoutes(panel, options))
}

/**
 * Helper to get panel from route
 * @param {Object} route - Vue Router route object
 * @returns {string|null} Panel key or null
 */
export function getPanelFromRoute(route) {
  return route.meta?.panel || null
}

/**
 * Helper to get resource from route
 * @param {Object} route - Vue Router route object
 * @returns {string|null} Resource key or null
 */
export function getResourceFromRoute(route) {
  return route.meta?.resource || route.params?.resource || null
}

export default {
  generatePanelRoutes,
  loadDynamicPanelRoutes,
  createPanelGuard,
  generateStaticPanelRoutes,
  getPanelFromRoute,
  getResourceFromRoute
}
