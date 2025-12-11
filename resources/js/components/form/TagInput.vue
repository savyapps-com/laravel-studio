<template>
  <div class="relative">
    <!-- Tags container with input -->
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
      <!-- Tags -->
      <span
        v-for="(tag, index) in internalValue"
        :key="`tag-${index}`"
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-200"
      >
        <span class="truncate max-w-[200px]">{{ tag }}</span>
        <button
          v-if="!disabled"
          type="button"
          @click.stop="removeTag(index)"
          class="flex-shrink-0 text-primary-600 hover:text-primary-900 dark:text-primary-300 dark:hover:text-primary-100 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </span>

      <!-- Input -->
      <input
        ref="inputRef"
        v-model="inputValue"
        type="text"
        :placeholder="internalValue.length === 0 ? placeholder : ''"
        :disabled="disabled"
        class="flex-1 min-w-[120px] border-none outline-none bg-transparent text-sm p-0 m-0 focus:ring-0 disabled:bg-transparent disabled:cursor-not-allowed"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
        @input="handleInput"
      />
    </div>

    <!-- Suggestions dropdown -->
    <div
      v-show="showSuggestions && filteredSuggestions.length > 0"
      class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-auto"
    >
      <div
        v-for="(suggestion, index) in filteredSuggestions"
        :key="`suggestion-${index}`"
        :class="[
          'px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-sm',
          {
            'bg-primary-100 dark:bg-primary-800': index === selectedIndex
          }
        ]"
        @mousedown.prevent="addTag(suggestion)"
      >
        {{ suggestion }}
      </div>
    </div>

    <!-- Helper text -->
    <p v-if="maxTags && internalValue.length > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
      {{ internalValue.length }} / {{ maxTags }} tags
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  suggestions: {
    type: Array,
    default: () => []
  },
  allowCustom: {
    type: Boolean,
    default: true
  },
  maxTags: {
    type: Number,
    default: null
  },
  minTags: {
    type: Number,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Add a tag...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  caseInsensitive: {
    type: Boolean,
    default: true
  },
  delimiter: {
    type: String,
    default: null
  },
  hasError: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)
const inputValue = ref('')
const isFocused = ref(false)
const showSuggestions = ref(false)
const selectedIndex = ref(-1)

// Internal value management
const internalValue = computed({
  get: () => Array.isArray(props.modelValue) ? props.modelValue : [],
  set: (val) => emit('update:modelValue', val)
})

// Filter suggestions based on input and already selected tags
const filteredSuggestions = computed(() => {
  const search = inputValue.value.toLowerCase().trim()

  return props.suggestions.filter(suggestion => {
    const suggestionLower = props.caseInsensitive ? suggestion.toLowerCase() : suggestion
    const matchesSearch = search === '' || suggestionLower.includes(search)
    const notAlreadySelected = !internalValue.value.some(tag =>
      props.caseInsensitive
        ? tag.toLowerCase() === suggestionLower
        : tag === suggestion
    )
    return matchesSearch && notAlreadySelected
  })
})

// Check if max tags reached
const maxTagsReached = computed(() => {
  return props.maxTags !== null && internalValue.value.length >= props.maxTags
})

// Focus the input
const focusInput = () => {
  if (!props.disabled) {
    inputRef.value?.focus()
  }
}

// Handle focus
const handleFocus = () => {
  isFocused.value = true
  showSuggestions.value = true
  selectedIndex.value = -1
}

// Handle blur
const handleBlur = () => {
  isFocused.value = false
  // Delay hiding suggestions to allow click events to fire
  setTimeout(() => {
    showSuggestions.value = false
    // Add tag if there's text in the input when blurring
    if (inputValue.value.trim() && props.allowCustom && !maxTagsReached.value) {
      addTag(inputValue.value.trim())
    }
  }, 150)
}

// Handle input changes
const handleInput = () => {
  showSuggestions.value = true
  selectedIndex.value = -1

  // Check for delimiter
  if (props.delimiter && inputValue.value.includes(props.delimiter)) {
    const parts = inputValue.value.split(props.delimiter)
    parts.forEach(part => {
      const trimmed = part.trim()
      if (trimmed) {
        addTag(trimmed)
      }
    })
    inputValue.value = ''
  }
}

// Handle keyboard navigation
const handleKeydown = (event) => {
  const { key } = event

  switch (key) {
    case 'Enter':
      event.preventDefault()
      if (selectedIndex.value >= 0 && filteredSuggestions.value[selectedIndex.value]) {
        addTag(filteredSuggestions.value[selectedIndex.value])
      } else if (inputValue.value.trim() && props.allowCustom) {
        addTag(inputValue.value.trim())
      }
      break

    case 'Backspace':
      if (inputValue.value === '' && internalValue.value.length > 0) {
        removeTag(internalValue.value.length - 1)
      }
      break

    case 'ArrowDown':
      event.preventDefault()
      if (filteredSuggestions.value.length > 0) {
        selectedIndex.value = Math.min(selectedIndex.value + 1, filteredSuggestions.value.length - 1)
      }
      break

    case 'ArrowUp':
      event.preventDefault()
      if (filteredSuggestions.value.length > 0) {
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
      }
      break

    case 'Escape':
      showSuggestions.value = false
      selectedIndex.value = -1
      break

    case 'Tab':
      if (inputValue.value.trim() && props.allowCustom && !maxTagsReached.value) {
        event.preventDefault()
        addTag(inputValue.value.trim())
      }
      break
  }
}

// Add a tag
const addTag = (tag) => {
  if (maxTagsReached.value) return

  const normalizedTag = props.caseInsensitive ? tag.toLowerCase() : tag
  const isDuplicate = internalValue.value.some(t =>
    props.caseInsensitive ? t.toLowerCase() === normalizedTag : t === tag
  )

  if (!isDuplicate) {
    // Check if it's a suggestion or custom is allowed
    const isSuggestion = props.suggestions.some(s =>
      props.caseInsensitive ? s.toLowerCase() === normalizedTag : s === tag
    )

    if (isSuggestion || props.allowCustom) {
      internalValue.value = [...internalValue.value, tag]
    }
  }

  inputValue.value = ''
  selectedIndex.value = -1

  nextTick(() => {
    inputRef.value?.focus()
  })
}

// Remove a tag
const removeTag = (index) => {
  const newValue = [...internalValue.value]
  newValue.splice(index, 1)
  internalValue.value = newValue
}

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
  if (!Array.isArray(newVal)) {
    emit('update:modelValue', [])
  }
}, { immediate: true })
</script>
