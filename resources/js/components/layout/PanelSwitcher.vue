<script setup>
/**
 * PanelSwitcher Component
 * Allows users to switch between accessible panels
 */
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePanelStore } from '../../stores/panel'
import Icon from '../common/Icon.vue'

const props = defineProps({
  // Display mode: 'dropdown' or 'list'
  mode: {
    type: String,
    default: 'dropdown',
    validator: (value) => ['dropdown', 'list'].includes(value)
  },
  // Show label with icon
  showLabel: {
    type: Boolean,
    default: true
  },
  // Button size: 'sm', 'md', 'lg'
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

const emit = defineEmits(['switch', 'before-switch', 'after-switch'])

const router = useRouter()
const panelStore = usePanelStore()

const isOpen = ref(false)
const isSwitching = ref(false)

const currentPanel = computed(() => panelStore.currentPanelObject)
const otherPanels = computed(() => panelStore.otherPanels)
const hasMultiplePanels = computed(() => panelStore.hasMultiplePanels)

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'text-sm px-2 py-1',
    md: 'text-base px-3 py-2',
    lg: 'text-lg px-4 py-3'
  }
  return sizes[props.size]
})

const iconSize = computed(() => {
  const sizes = { sm: 'w-4 h-4', md: 'w-5 h-5', lg: 'w-6 h-6' }
  return sizes[props.size]
})

async function handleSwitch(panelKey) {
  if (isSwitching.value) return

  emit('before-switch', panelKey)
  isSwitching.value = true
  isOpen.value = false

  try {
    const path = await panelStore.switchPanel(panelKey)
    emit('switch', panelKey)

    // Navigate to the new panel's dashboard
    if (path) {
      await router.push(path)
    }

    emit('after-switch', panelKey)
  } catch (error) {
    console.error('Failed to switch panel:', error)
  } finally {
    isSwitching.value = false
  }
}

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function closeDropdown() {
  isOpen.value = false
}
</script>

<template>
  <div v-if="hasMultiplePanels" class="panel-switcher relative">
    <!-- Dropdown Mode -->
    <template v-if="mode === 'dropdown'">
      <button
        type="button"
        class="panel-switcher-btn flex items-center gap-2 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
        :class="sizeClasses"
        @click="toggleDropdown"
        @blur="closeDropdown"
      >
        <Icon
          v-if="currentPanel?.icon"
          :name="currentPanel.icon"
          :class="iconSize"
          class="text-gray-500 dark:text-gray-400"
        />
        <span v-if="showLabel" class="font-medium text-gray-700 dark:text-gray-200">
          {{ currentPanel?.label }}
        </span>
        <Icon
          name="chevron-down"
          :class="[iconSize, { 'rotate-180': isOpen }]"
          class="text-gray-400 transition-transform"
        />
      </button>

      <!-- Dropdown Menu -->
      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700"
        >
          <div class="py-1">
            <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
              Switch to
            </div>
            <button
              v-for="panel in otherPanels"
              :key="panel.key"
              type="button"
              :disabled="isSwitching"
              class="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 dark:text-gray-200 dark:hover:bg-gray-700"
              @mousedown.prevent="handleSwitch(panel.key)"
            >
              <Icon
                v-if="panel.icon"
                :name="panel.icon"
                class="w-5 h-5 text-gray-400"
              />
              <span>{{ panel.label }}</span>
            </button>
          </div>
        </div>
      </Transition>
    </template>

    <!-- List Mode -->
    <template v-else-if="mode === 'list'">
      <div class="panel-switcher-list space-y-1">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 dark:text-gray-400">
          Switch Panel
        </div>
        <button
          v-for="panel in otherPanels"
          :key="panel.key"
          type="button"
          :disabled="isSwitching"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 transition-colors dark:text-gray-200 dark:hover:bg-gray-700"
          @click="handleSwitch(panel.key)"
        >
          <Icon
            v-if="panel.icon"
            :name="panel.icon"
            class="w-5 h-5 text-gray-400"
          />
          <span>{{ panel.label }}</span>
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.panel-switcher {
  position: relative;
}
</style>
