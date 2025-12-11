<template>
  <div ref="containerRef" class="relative">
    <!-- Selected items display / Input container -->
    <div
      :class="[
        'form-input min-h-[42px] flex flex-wrap items-center gap-1.5 py-1.5 px-2 cursor-text',
        {
          'form-input-error': hasError,
          'form-input-disabled': disabled,
          'ring-2 ring-primary-500 border-primary-500': isFocused
        }
      ]"
      @click="focusInput"
    >
      <!-- Selected tags -->
      <span
        v-for="(item, index) in selectedItemsDisplay"
        :key="`selected-${item.value}-${index}`"
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-200"
      >
        <span class="truncate max-w-[200px]">{{ item.label }}</span>
        <button
          v-if="!disabled"
          type="button"
          @click.stop="removeItem(item.value)"
          class="flex-shrink-0 text-primary-600 hover:text-primary-900 dark:text-primary-300 dark:hover:text-primary-100 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </span>

      <!-- Search input -->
      <input
        v-if="searchable && !maxSelectionsReached"
        ref="inputRef"
        v-model="searchQuery"
        type="text"
        :placeholder="selectedItemsDisplay.length === 0 ? placeholder : ''"
        :disabled="disabled || maxSelectionsReached"
        class="flex-1 min-w-[120px] border-none outline-none bg-transparent text-sm p-0 m-0 focus:ring-0 disabled:bg-transparent disabled:cursor-not-allowed"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Dropdown toggle button -->
      <button
        type="button"
        @click.stop="toggleDropdown"
        :disabled="disabled || maxSelectionsReached"
        class="ml-auto flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        :class="{ 'opacity-50 cursor-not-allowed': disabled || maxSelectionsReached }"
      >
        <div v-if="isLoading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600" />
        <svg v-else class="w-5 h-5 transition-transform" :class="{ 'rotate-180': showDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
    </div>

    <!-- Dropdown -->
    <Teleport to="body">
      <div
        v-if="showDropdown"
        ref="dropdownRef"
        :style="dropdownStyle"
        class="fixed z-[9999] bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl overflow-hidden"
      >
        <!-- Loading state -->
        <div v-if="isLoading" class="px-4 py-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto mb-2" />
          <p class="text-sm text-gray-500 dark:text-gray-400">Loading options...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="loadError" class="px-4 py-6 text-center">
          <svg class="w-10 h-10 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ loadError }}</p>
          <button
            type="button"
            @click="fetchOptions"
            class="mt-2 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400"
          >
            Try again
          </button>
        </div>

        <!-- Options list -->
        <template v-else>
          <!-- Grouped options -->
          <div v-if="groupBy" class="max-h-64 overflow-y-auto">
            <template v-for="(groupOptions, groupName) in groupedOptions" :key="groupName">
              <div class="sticky top-0 px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                {{ groupName }}
              </div>
              <div
                v-for="option in groupOptions"
                :key="`${groupName}-${option.value}`"
                :class="[
                  'px-4 py-2.5 cursor-pointer flex items-center gap-3 transition-colors',
                  isSelected(option.value)
                    ? 'bg-primary-50 dark:bg-primary-900/30'
                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                  { 'opacity-50 cursor-not-allowed': maxSelectionsReached && !isSelected(option.value) }
                ]"
                @click="toggleOption(option)"
              >
                <!-- Checkbox indicator -->
                <div
                  :class="[
                    'w-4 h-4 rounded border-2 flex items-center justify-center transition-colors',
                    isSelected(option.value)
                      ? 'bg-primary-500 border-primary-500'
                      : 'border-gray-300 dark:border-gray-600'
                  ]"
                >
                  <svg v-if="isSelected(option.value)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>

                <!-- Option content -->
                <div class="flex-1 min-w-0">
                  <div class="text-sm text-gray-900 dark:text-gray-100 truncate">
                    {{ option.label }}
                  </div>
                  <div v-if="option.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ option.description }}
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Flat options -->
          <div v-else class="max-h-64 overflow-y-auto">
            <div
              v-for="option in filteredOptions"
              :key="option.value"
              :class="[
                'px-4 py-2.5 cursor-pointer flex items-center gap-3 transition-colors',
                isSelected(option.value)
                  ? 'bg-primary-50 dark:bg-primary-900/30'
                  : 'hover:bg-gray-50 dark:hover:bg-gray-700/50',
                { 'opacity-50 cursor-not-allowed': maxSelectionsReached && !isSelected(option.value) }
              ]"
              @click="toggleOption(option)"
            >
              <!-- Checkbox indicator -->
              <div
                :class="[
                  'w-4 h-4 rounded border-2 flex items-center justify-center transition-colors',
                  isSelected(option.value)
                    ? 'bg-primary-500 border-primary-500'
                    : 'border-gray-300 dark:border-gray-600'
                ]"
              >
                <svg v-if="isSelected(option.value)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>

              <!-- Option content -->
              <div class="flex-1 min-w-0">
                <div class="text-sm text-gray-900 dark:text-gray-100 truncate">
                  {{ option.label }}
                </div>
                <div v-if="option.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                  {{ option.description }}
                </div>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredOptions.length === 0" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
              <svg class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <p v-if="searchQuery">No results for "{{ searchQuery }}"</p>
              <p v-else>No options available</p>
            </div>
          </div>
        </template>

        <!-- Footer with selection count -->
        <div v-if="!isLoading && !loadError && options.length > 0" class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-between text-xs">
          <span class="text-gray-500 dark:text-gray-400">
            {{ internalValue.length }} selected
            <span v-if="maxSelections"> / {{ maxSelections }} max</span>
          </span>
          <button
            v-if="internalValue.length > 0"
            type="button"
            @click="clearAll"
            class="text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"
          >
            Clear all
          </button>
        </div>
      </div>
    </Teleport>

    <!-- Helper text -->
    <p v-if="maxSelections && internalValue.length > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
      {{ internalValue.length }} / {{ maxSelections }} selected
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import axiosModule from 'axios'

// Use window.axios if available (has auth interceptors), otherwise use imported axios
const axios = window.axios || axiosModule

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  endpoint: {
    type: String,
    required: true
  },
  labelKey: {
    type: String,
    default: 'label'
  },
  valueKey: {
    type: String,
    default: 'value'
  },
  descriptionKey: {
    type: String,
    default: null
  },
  groupBy: {
    type: String,
    default: null
  },
  searchable: {
    type: Boolean,
    default: true
  },
  maxSelections: {
    type: Number,
    default: null
  },
  minSelections: {
    type: Number,
    default: null
  },
  showTags: {
    type: Boolean,
    default: true
  },
  headers: {
    type: Object,
    default: () => ({})
  },
  placeholder: {
    type: String,
    default: 'Select options...'
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

// Refs
const containerRef = ref(null)
const dropdownRef = ref(null)
const inputRef = ref(null)

// State
const isFocused = ref(false)
const showDropdown = ref(false)
const searchQuery = ref('')
const isLoading = ref(false)
const loadError = ref(null)
const options = ref([])
const dropdownStyle = ref({})

// Internal value management
const internalValue = computed({
  get: () => Array.isArray(props.modelValue) ? props.modelValue : [],
  set: (val) => emit('update:modelValue', val)
})

// Check if max selections reached
const maxSelectionsReached = computed(() => {
  return props.maxSelections !== null && internalValue.value.length >= props.maxSelections
})

// Get display items for selected values
const selectedItemsDisplay = computed(() => {
  if (!props.showTags) return []

  return internalValue.value.map(value => {
    const option = options.value.find(o => o.value === value)
    return {
      value,
      label: option ? option.label : value
    }
  })
})

// Filter options based on search query
const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) {
    return options.value
  }

  const query = searchQuery.value.toLowerCase()
  return options.value.filter(option =>
    option.label.toLowerCase().includes(query) ||
    (option.description && option.description.toLowerCase().includes(query))
  )
})

// Group options if groupBy is set
const groupedOptions = computed(() => {
  if (!props.groupBy) return {}

  const groups = {}
  filteredOptions.value.forEach(option => {
    const group = option.group || 'Other'
    if (!groups[group]) {
      groups[group] = []
    }
    groups[group].push(option)
  })

  return groups
})

// Check if an option is selected
const isSelected = (value) => {
  return internalValue.value.includes(value)
}

// Toggle option selection
const toggleOption = (option) => {
  if (props.disabled) return

  const value = option.value
  const currentValues = [...internalValue.value]

  if (isSelected(value)) {
    // Remove
    const index = currentValues.indexOf(value)
    currentValues.splice(index, 1)
    internalValue.value = currentValues
  } else {
    // Add (if not at max)
    if (!maxSelectionsReached.value) {
      currentValues.push(value)
      internalValue.value = currentValues
    }
  }
}

// Remove a single item
const removeItem = (value) => {
  if (props.disabled) return

  const currentValues = [...internalValue.value]
  const index = currentValues.indexOf(value)
  if (index > -1) {
    currentValues.splice(index, 1)
    internalValue.value = currentValues
  }
}

// Clear all selections
const clearAll = () => {
  if (props.disabled) return
  internalValue.value = []
}

// Fetch options from endpoint
const fetchOptions = async () => {
  if (!props.endpoint) return

  isLoading.value = true
  loadError.value = null

  try {
    const response = await axios.get(props.endpoint, {
      headers: props.headers
    })

    const data = Array.isArray(response.data) ? response.data : (response.data.data || [])

    options.value = data.map(item => ({
      value: item[props.valueKey],
      label: item[props.labelKey],
      description: props.descriptionKey ? item[props.descriptionKey] : null,
      group: props.groupBy ? item[props.groupBy] : null
    }))
  } catch (error) {
    console.error('Failed to fetch options:', error)
    loadError.value = 'Failed to load options'
    options.value = []
  } finally {
    isLoading.value = false
  }
}

// Focus the input
const focusInput = () => {
  if (!props.disabled && inputRef.value && !maxSelectionsReached.value) {
    inputRef.value.focus()
  }
}

// Handle focus
const handleFocus = () => {
  isFocused.value = true
  showDropdown.value = true
  updateDropdownPosition()
}

// Handle blur
const handleBlur = () => {
  setTimeout(() => {
    isFocused.value = false
  }, 150)
}

// Toggle dropdown
const toggleDropdown = () => {
  if (props.disabled || maxSelectionsReached.value) return

  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    updateDropdownPosition()
    nextTick(() => {
      inputRef.value?.focus()
    })
  }
}

// Handle keyboard navigation
const handleKeydown = (event) => {
  const { key } = event

  if (key === 'Escape') {
    showDropdown.value = false
    searchQuery.value = ''
  } else if (key === 'Backspace' && !searchQuery.value && internalValue.value.length > 0) {
    // Remove last item
    const newValue = [...internalValue.value]
    newValue.pop()
    internalValue.value = newValue
  }
}

// Calculate dropdown position
const updateDropdownPosition = () => {
  if (!containerRef.value) return

  const rect = containerRef.value.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const dropdownHeight = 350

  const spaceBelow = viewportHeight - rect.bottom
  const openAbove = spaceBelow < dropdownHeight && rect.top > dropdownHeight

  dropdownStyle.value = {
    top: openAbove ? 'auto' : `${rect.bottom + 4}px`,
    bottom: openAbove ? `${viewportHeight - rect.top + 4}px` : 'auto',
    left: `${rect.left}px`,
    width: `${rect.width}px`,
    minWidth: '280px'
  }
}

// Close on outside click
const handleClickOutside = (event) => {
  if (
    containerRef.value &&
    !containerRef.value.contains(event.target) &&
    dropdownRef.value &&
    !dropdownRef.value.contains(event.target)
  ) {
    showDropdown.value = false
    searchQuery.value = ''
  }
}

// Handle scroll and resize
const handleScrollOrResize = () => {
  if (showDropdown.value) {
    updateDropdownPosition()
  }
}

// Lifecycle
onMounted(() => {
  fetchOptions()
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('scroll', handleScrollOrResize, true)
  window.addEventListener('resize', handleScrollOrResize)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScrollOrResize, true)
  window.removeEventListener('resize', handleScrollOrResize)
})

// Watch for external value changes
watch(() => props.modelValue, (newVal) => {
  if (!Array.isArray(newVal)) {
    emit('update:modelValue', [])
  }
}, { immediate: true })

// Watch endpoint changes
watch(() => props.endpoint, () => {
  fetchOptions()
})
</script>
