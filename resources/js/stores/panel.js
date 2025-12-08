/**
 * Panel Store - Pinia Store for Multi-Panel System
 * Manages current panel state, accessible panels, and panel switching
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { panelService } from '../services/panelService'

// Helper function to get panel from localStorage
const getPanelFromStorage = () => {
  return localStorage.getItem('current_panel') || null
}

// Helper function to save panel to localStorage
const savePanelToStorage = (panel) => {
  if (panel) {
    localStorage.setItem('current_panel', panel)
  } else {
    localStorage.removeItem('current_panel')
  }
}

export const usePanelStore = defineStore('panel', () => {
  // State
  const currentPanel = ref(getPanelFromStorage())
  const panelConfig = ref(null)
  const accessiblePanels = ref([])
  const defaultPanel = ref(null)
  const isLoading = ref(false)
  const isInitialized = ref(false)

  // Getters
  const menuItems = computed(() => panelConfig.value?.menu ?? [])
  const resources = computed(() => panelConfig.value?.resources ?? [])
  const features = computed(() => panelConfig.value?.features ?? [])
  const panelLabel = computed(() => panelConfig.value?.label ?? '')
  const panelPath = computed(() => panelConfig.value?.path ?? '')
  const panelIcon = computed(() => panelConfig.value?.icon ?? '')
  const panelSettings = computed(() => panelConfig.value?.settings ?? {})

  // Check if user has access to multiple panels
  const hasMultiplePanels = computed(() => accessiblePanels.value.length > 1)

  // Get other panels user can switch to
  const otherPanels = computed(() =>
    accessiblePanels.value.filter(p => p.key !== currentPanel.value)
  )

  // Get current panel object
  const currentPanelObject = computed(() =>
    accessiblePanels.value.find(p => p.key === currentPanel.value) || null
  )

  // Actions
  async function loadAccessiblePanels() {
    isLoading.value = true
    try {
      const response = await panelService.getAccessiblePanels()
      accessiblePanels.value = response.panels
      defaultPanel.value = response.default

      // If no current panel is set, use the default
      if (!currentPanel.value && response.default) {
        currentPanel.value = response.default
        savePanelToStorage(response.default)
      }

      return response
    } finally {
      isLoading.value = false
    }
  }

  async function loadPanelConfig(panel) {
    if (!panel) return null

    isLoading.value = true
    try {
      const config = await panelService.getPanelConfig(panel)
      currentPanel.value = panel
      panelConfig.value = config
      savePanelToStorage(panel)
      return config
    } catch (error) {
      // If access denied, try to find an accessible panel
      if (error.response?.status === 403) {
        const fallback = await findAccessiblePanel()
        if (fallback) {
          return loadPanelConfig(fallback)
        }
      }
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function findAccessiblePanel() {
    if (accessiblePanels.value.length === 0) {
      await loadAccessiblePanels()
    }
    return defaultPanel.value || accessiblePanels.value[0]?.key || null
  }

  function hasResource(resource) {
    return resources.value.includes(resource)
  }

  function hasFeature(feature) {
    return features.value.includes(feature)
  }

  async function switchPanel(panelKey) {
    const panel = accessiblePanels.value.find(p => p.key === panelKey)
    if (!panel) {
      throw new Error(`Panel not found: ${panelKey}`)
    }

    await loadPanelConfig(panelKey)
    return panel.path
  }

  async function initialize() {
    if (isInitialized.value) return

    isLoading.value = true
    try {
      // Load accessible panels first
      await loadAccessiblePanels()

      // Then load the current panel config
      const panel = currentPanel.value || defaultPanel.value
      if (panel) {
        await loadPanelConfig(panel)
      }

      isInitialized.value = true
    } finally {
      isLoading.value = false
    }
  }

  function reset() {
    currentPanel.value = null
    panelConfig.value = null
    accessiblePanels.value = []
    defaultPanel.value = null
    isInitialized.value = false
    localStorage.removeItem('current_panel')
  }

  // Get the API base URL for a resource in the current panel
  function getResourceApiUrl(resource) {
    if (!currentPanel.value) {
      throw new Error('No panel is currently active')
    }
    return panelService.getResourceUrl(currentPanel.value, resource)
  }

  // Detect panel from route path
  function detectPanelFromPath(path) {
    for (const panel of accessiblePanels.value) {
      if (path.startsWith(panel.path)) {
        return panel.key
      }
    }
    return null
  }

  return {
    // State
    currentPanel,
    panelConfig,
    accessiblePanels,
    defaultPanel,
    isLoading,
    isInitialized,
    // Getters
    menuItems,
    resources,
    features,
    panelLabel,
    panelPath,
    panelIcon,
    panelSettings,
    hasMultiplePanels,
    otherPanels,
    currentPanelObject,
    // Actions
    loadAccessiblePanels,
    loadPanelConfig,
    findAccessiblePanel,
    hasResource,
    hasFeature,
    switchPanel,
    initialize,
    reset,
    getResourceApiUrl,
    detectPanelFromPath
  }
})
