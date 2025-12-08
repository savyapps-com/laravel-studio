<script setup>
/**
 * PanelLayout Component
 * Wrapper layout for panel-based pages
 * Automatically initializes panel store and provides panel context
 */
import { computed, onMounted, watch, provide } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePanelStore } from '../../stores/panel'
import PanelSwitcher from './PanelSwitcher.vue'

const props = defineProps({
  // Override panel detection from route
  panel: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['panel-loaded', 'panel-changed', 'panel-error'])

const route = useRoute()
const router = useRouter()
const panelStore = usePanelStore()

// Detect current panel from route or props
const currentPanel = computed(() => {
  if (props.panel) return props.panel

  // Try to detect from route path
  const detected = panelStore.detectPanelFromPath(route.path)
  if (detected) return detected

  // Try to detect from route meta
  return route.meta?.panel || panelStore.currentPanel
})

// Provide panel context to child components
provide('currentPanel', currentPanel)
provide('panelStore', panelStore)

// Load panel config when panel changes
watch(currentPanel, async (panel, oldPanel) => {
  if (panel && panel !== oldPanel) {
    try {
      await panelStore.loadPanelConfig(panel)
      emit('panel-changed', panel)
    } catch (error) {
      console.error('Failed to load panel config:', error)
      emit('panel-error', error)
    }
  }
}, { immediate: true })

// Initialize panel store on mount
onMounted(async () => {
  try {
    if (!panelStore.isInitialized) {
      await panelStore.initialize()
    }

    // Load current panel config if we have a panel
    if (currentPanel.value && !panelStore.panelConfig) {
      await panelStore.loadPanelConfig(currentPanel.value)
    }

    emit('panel-loaded', currentPanel.value)
  } catch (error) {
    console.error('Failed to initialize panel:', error)
    emit('panel-error', error)
  }
})

// Handle panel switch
async function handlePanelSwitch(panelKey) {
  const panel = panelStore.accessiblePanels.find(p => p.key === panelKey)
  if (panel?.path) {
    await router.push(panel.path)
  }
}

// Computed properties for template
const isLoading = computed(() => panelStore.isLoading)
const panelLabel = computed(() => panelStore.panelLabel)
const panelIcon = computed(() => panelStore.panelIcon)
const menuItems = computed(() => panelStore.menuItems)
const hasMultiplePanels = computed(() => panelStore.hasMultiplePanels)
const panelSettings = computed(() => panelStore.panelSettings)
</script>

<template>
  <div
    class="panel-layout"
    :class="[
      `panel-${currentPanel}`,
      `layout-${panelSettings.layout || 'classic'}`,
      `theme-${panelSettings.theme || 'light'}`
    ]"
  >
    <!-- Loading State -->
    <div v-if="isLoading && !panelStore.isInitialized" class="panel-loading">
      <slot name="loading">
        <div class="flex items-center justify-center min-h-screen">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
      </slot>
    </div>

    <!-- Panel Content -->
    <template v-else>
      <!-- Header Slot (for custom headers) -->
      <slot name="header" :panel="currentPanel" :label="panelLabel" :icon="panelIcon">
        <!-- Default Header with Panel Switcher -->
        <header v-if="$slots.default" class="panel-header">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ panelLabel }}
              </h1>
            </div>

            <!-- Panel Switcher -->
            <PanelSwitcher
              v-if="hasMultiplePanels"
              mode="dropdown"
              @switch="handlePanelSwitch"
            />
          </div>
        </header>
      </slot>

      <!-- Sidebar Slot (for navigation) -->
      <slot
        name="sidebar"
        :menu-items="menuItems"
        :panel="currentPanel"
      />

      <!-- Main Content -->
      <main class="panel-content">
        <slot
          :panel="currentPanel"
          :menu-items="menuItems"
          :panel-store="panelStore"
        />
      </main>

      <!-- Footer Slot -->
      <slot name="footer" :panel="currentPanel" />
    </template>
  </div>
</template>

<style scoped>
.panel-layout {
  min-height: 100vh;
}

.panel-loading {
  min-height: 100vh;
}

.panel-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--color-gray-200);
}

:root.dark .panel-header {
  border-color: var(--color-gray-700);
}

.panel-content {
  flex: 1;
}
</style>
