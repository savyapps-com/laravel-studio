<template>
  <div class="w-full">
    <div class="flex flex-wrap items-center gap-2 p-2 min-h-[42px] border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
      <!-- Tags -->
      <span
        v-for="(tag, index) in modelValue"
        :key="index"
        class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-medium rounded-md"
      >
        {{ tag }}
        <button
          type="button"
          @click="removeTag(index)"
          class="p-0.5 hover:bg-primary-200 dark:hover:bg-primary-800 rounded transition-colors"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </span>

      <!-- Input -->
      <input
        ref="inputRef"
        v-model="inputValue"
        type="text"
        :placeholder="modelValue.length === 0 ? placeholder : ''"
        class="flex-1 min-w-[120px] outline-none bg-transparent text-title placeholder:text-gray-400"
        @keydown.enter.prevent="addTag"
        @keydown.backspace="handleBackspace"
        @blur="addTag"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Add tag and press Enter'
  }
})

const emit = defineEmits(['update:modelValue'])

const inputValue = ref('')
const inputRef = ref(null)

function addTag() {
  const value = inputValue.value.trim()
  if (value && !props.modelValue.includes(value)) {
    emit('update:modelValue', [...props.modelValue, value])
    inputValue.value = ''
  }
}

function removeTag(index) {
  const newTags = [...props.modelValue]
  newTags.splice(index, 1)
  emit('update:modelValue', newTags)
}

function handleBackspace() {
  if (inputValue.value === '' && props.modelValue.length > 0) {
    removeTag(props.modelValue.length - 1)
  }
}
</script>
