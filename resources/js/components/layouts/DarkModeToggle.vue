<template>
  <button
    @click="toggleDarkMode"
    v-tooltip="isDark ? lightModeTooltip : darkModeTooltip"
    class="btn-ghost"
    aria-label="Toggle dark mode"
  >
    <Icon v-if="isDark" name="sun" :size="iconSize" class="text-yellow-500" />
    <Icon v-else name="moon" :size="iconSize" class="text-gray-600 dark:text-gray-400" />
  </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Icon from '../common/Icon.vue'

const props = defineProps({
  iconSize: {
    type: Number,
    default: 20
  },
  darkModeTooltip: {
    type: String,
    default: 'Switch to dark mode'
  },
  lightModeTooltip: {
    type: String,
    default: 'Switch to light mode'
  },
  storageKey: {
    type: String,
    default: 'setting_dark_mode'
  },
  /**
   * Optional callback to save the dark mode preference to a backend/database
   * @type {Function|null}
   */
  onSave: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['change'])

const isDark = ref(false)

onMounted(() => {
  // Check if dark mode is enabled
  isDark.value = document.documentElement.classList.contains('dark')
})

const toggleDarkMode = async () => {
  isDark.value = !isDark.value

  // Apply dark mode class to HTML element
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }

  // Save to localStorage
  localStorage.setItem(props.storageKey, isDark.value ? 'dark' : 'light')

  // Emit change event
  emit('change', isDark.value)

  // Call optional save callback
  if (props.onSave) {
    try {
      await props.onSave(isDark.value)
    } catch (error) {
      console.error('Failed to save dark mode preference:', error)
    }
  }
}
</script>
