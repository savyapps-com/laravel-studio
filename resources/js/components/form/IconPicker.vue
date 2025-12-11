<template>
  <div class="relative" ref="containerRef">
    <!-- Selected Icon Display / Trigger -->
    <div
      :class="[
        'form-input min-h-[42px] flex items-center gap-2 px-3 cursor-pointer',
        {
          'form-input-error': hasError,
          'form-input-disabled': disabled,
          'ring-2 ring-primary-500 border-primary-500': isOpen
        }
      ]"
      @click="togglePicker"
    >
      <!-- Selected icon preview -->
      <div v-if="modelValue" class="flex items-center gap-2">
        <Icon :name="modelValue" class="w-5 h-5 text-gray-700 dark:text-gray-300" />
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ modelValue }}</span>
      </div>
      <span v-else class="text-gray-400 text-sm">{{ placeholder }}</span>

      <!-- Clear button -->
      <button
        v-if="modelValue && !disabled"
        type="button"
        @click.stop="clearSelection"
        class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Dropdown indicator -->
      <svg
        v-if="!modelValue"
        class="w-4 h-4 ml-auto text-gray-400"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Icon Picker Dropdown -->
    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="dropdownRef"
        :style="dropdownStyle"
        class="fixed z-[9999] w-80 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl"
      >
        <!-- Search input -->
        <div v-if="searchable" class="p-3 border-b border-gray-200 dark:border-gray-700">
          <input
            ref="searchRef"
            v-model="searchQuery"
            type="text"
            placeholder="Search icons..."
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>

        <!-- Icon Grid -->
        <div class="p-3 max-h-64 overflow-y-auto">
          <div
            v-if="filteredIcons.length > 0"
            class="grid gap-1"
            :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }"
          >
            <button
              v-for="icon in filteredIcons"
              :key="icon"
              type="button"
              :class="[
                'p-2 rounded-md transition-colors flex items-center justify-center',
                modelValue === icon
                  ? 'bg-primary-100 dark:bg-primary-800 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500'
                  : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'
              ]"
              :title="icon"
              @click="selectIcon(icon)"
            >
              <Icon :name="icon" :class="iconPreviewClass" />
            </button>
          </div>
          <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
            No icons found
          </div>
        </div>

        <!-- Selected icon info -->
        <div v-if="modelValue" class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
          <span class="text-xs text-gray-500 dark:text-gray-400">Selected: </span>
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ modelValue }}</span>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import Icon from '../common/Icon.vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  icons: {
    type: Array,
    default: () => []
  },
  searchable: {
    type: Boolean,
    default: true
  },
  columns: {
    type: Number,
    default: 6
  },
  previewSize: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  placeholder: {
    type: String,
    default: 'Select an icon...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  hasError: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const containerRef = ref(null)
const dropdownRef = ref(null)
const searchRef = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')
const dropdownStyle = ref({})

// Default icons available in the package
const defaultIcons = [
  // Navigation & UI
  'dashboard', 'menu', 'close', 'search', 'home', 'settings', 'cog',
  'chevron-down', 'chevron-up', 'chevron-right', 'chevron-left',
  'arrow-left', 'arrow-right', 'arrow-up', 'arrow-down', 'arrow-path',

  // Layout
  'layout', 'layout-sidebar', 'layout-navbar', 'layout-sidebar-right',
  'layout-sidebar-left', 'grid', 'list', 'table', 'squares-2x2',

  // Users & Security
  'user', 'users', 'user-plus', 'user-minus', 'user-check', 'team', 'profile',
  'shield', 'shield-exclamation', 'lock', 'unlock', 'key', 'logout',

  // Status & Alerts
  'check', 'check-circle', 'check-circle-filled', 'x-circle', 'x-mark',
  'alert-circle', 'alert-triangle', 'info-circle', 'help-circle', 'question',
  'badge-check',

  // Communication
  'mail', 'messages', 'chat', 'bell', 'inbox', 'paper-airplane', 'phone',

  // Data & Content
  'analytics', 'reports', 'tasks', 'clipboard', 'document-text', 'document-duplicate',
  'file', 'folder', 'folder-open', 'folder-plus', 'archive', 'newspaper',

  // Actions
  'edit', 'pencil', 'delete', 'trash', 'add', 'plus', 'minus',
  'save', 'download', 'upload', 'copy', 'duplicate', 'share',
  'refresh', 'undo', 'redo', 'eraser',

  // Media
  'image', 'video', 'camera', 'microphone', 'music-note', 'film', 'play', 'pause',
  'rotate-left', 'rotate-right', 'flip-horizontal', 'flip-vertical',
  'zoom-in', 'zoom-out', 'maximize', 'minimize',

  // Visual
  'eye', 'eye-off', 'eye-slash', 'sun', 'moon', 'palette', 'adjustments',

  // Commerce
  'shopping-cart', 'credit-card', 'currency-dollar', 'receipt', 'tag',

  // Favorites & Ratings
  'star', 'star-filled', 'heart', 'bookmark', 'bookmark-filled', 'flag',
  'thumbs-up', 'thumbs-down', 'pin',

  // Charts & Data Visualization
  'chart-bar', 'chart-line', 'chart-pie',

  // Tech & Development
  'code', 'code-2', 'code-bracket', 'globe', 'server', 'database',
  'cube-transparent', 'monitor', 'device-mobile',

  // Links & External
  'link', 'external-link', 'paperclip', 'at-symbol', 'hashtag',

  // Text Formatting
  'bold', 'italic', 'underline', 'strikethrough',
  'list-ordered', 'list-checks', 'align-left', 'align-center', 'align-right', 'quote',

  // Time & Calendar
  'clock', 'calendar',

  // Misc
  'more-vertical', 'dots-horizontal', 'bars-3', 'filter', 'sort',
  'lightning-bolt', 'squares-plus', 'cog-6-tooth',
  'arrows-pointing-out', 'arrows-pointing-in', 'circle', 'loading'
]

// Use custom icons if provided, otherwise use defaults
const availableIcons = computed(() => {
  return props.icons.length > 0 ? props.icons : defaultIcons
})

// Filter icons based on search query
const filteredIcons = computed(() => {
  if (!searchQuery.value) {
    return availableIcons.value
  }

  const query = searchQuery.value.toLowerCase()
  return availableIcons.value.filter(icon =>
    icon.toLowerCase().includes(query)
  )
})

// Icon preview size class
const iconPreviewClass = computed(() => {
  const sizes = {
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6'
  }
  return sizes[props.previewSize] || sizes.md
})

// Calculate dropdown position
const updateDropdownPosition = () => {
  if (!containerRef.value) return

  const rect = containerRef.value.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const dropdownHeight = 350 // Approximate max height

  // Determine if dropdown should open above or below
  const spaceBelow = viewportHeight - rect.bottom
  const openAbove = spaceBelow < dropdownHeight && rect.top > dropdownHeight

  dropdownStyle.value = {
    top: openAbove ? 'auto' : `${rect.bottom + 4}px`,
    bottom: openAbove ? `${viewportHeight - rect.top + 4}px` : 'auto',
    left: `${rect.left}px`,
    width: `${Math.max(rect.width, 320)}px`
  }
}

// Toggle picker
const togglePicker = () => {
  if (props.disabled) return

  isOpen.value = !isOpen.value

  if (isOpen.value) {
    updateDropdownPosition()
    nextTick(() => {
      searchRef.value?.focus()
    })
  }
}

// Select an icon
const selectIcon = (icon) => {
  emit('update:modelValue', icon)
  isOpen.value = false
  searchQuery.value = ''
}

// Clear selection
const clearSelection = () => {
  emit('update:modelValue', '')
}

// Close on outside click
const handleClickOutside = (event) => {
  if (
    containerRef.value &&
    !containerRef.value.contains(event.target) &&
    dropdownRef.value &&
    !dropdownRef.value.contains(event.target)
  ) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

// Close on escape
const handleKeydown = (event) => {
  if (event.key === 'Escape' && isOpen.value) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

// Handle scroll and resize
const handleScrollOrResize = () => {
  if (isOpen.value) {
    updateDropdownPosition()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleKeydown)
  window.addEventListener('scroll', handleScrollOrResize, true)
  window.addEventListener('resize', handleScrollOrResize)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('scroll', handleScrollOrResize, true)
  window.removeEventListener('resize', handleScrollOrResize)
})
</script>
