/**
 * useLayout Composable
 * Provides layout management utilities for admin panels
 *
 * @param {Object} options - Configuration options
 * @param {Object} options.settingsStore - The settings store instance (Pinia)
 */

import { computed } from 'vue'

export function useLayout(options = {}) {
  const { settingsStore = null } = options

  // If no settings store provided, return stub functions
  if (!settingsStore) {
    return {
      currentAdminLayout: computed(() => 'classic'),
      currentLayoutConfig: computed(() => null),
      hasSidebar: computed(() => true),
      hasHorizontalNav: computed(() => false),
      isContentConstrained: computed(() => false),
      switchLayout: async () => {
        console.warn('useLayout: settingsStore is required for switchLayout')
      },
    }
  }

  const currentAdminLayout = computed(() => settingsStore.currentAdminLayout)
  const adminLayouts = computed(() => settingsStore.adminLayouts || [])

  /**
   * Get current layout configuration
   */
  const currentLayoutConfig = computed(() => {
    return adminLayouts.value.find(l => l.value === currentAdminLayout.value)
  })

  /**
   * Check if current layout has sidebar
   */
  const hasSidebar = computed(() => {
    const navType = currentLayoutConfig.value?.metadata?.navigation_type
    return ['vertical', 'vertical-icons', 'vertical-mini'].includes(navType)
  })

  /**
   * Check if current layout has horizontal navigation
   */
  const hasHorizontalNav = computed(() => {
    const navType = currentLayoutConfig.value?.metadata?.navigation_type
    return navType === 'horizontal'
  })

  /**
   * Check if content is constrained width
   */
  const isContentConstrained = computed(() => {
    const contentWidth = currentLayoutConfig.value?.metadata?.content_width
    return contentWidth === 'constrained'
  })

  /**
   * Switch to a different layout
   */
  async function switchLayout(layoutName) {
    if (settingsStore.updateUserLayout) {
      return await settingsStore.updateUserLayout(layoutName)
    }
    console.warn('useLayout: settingsStore.updateUserLayout is not available')
  }

  return {
    currentAdminLayout,
    currentLayoutConfig,
    hasSidebar,
    hasHorizontalNav,
    isContentConstrained,
    switchLayout,
  }
}
