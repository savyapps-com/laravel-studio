<template>
  <div
    v-if="isImpersonating"
    class="bg-warning-600 text-white px-4 py-3 flex items-center justify-between shadow-lg sticky top-0 z-50"
  >
    <div class="flex items-center gap-3">
      <Icon name="warning" :size="20" />
      <div class="text-sm">
        <span class="font-semibold">{{ impersonatingLabel }}</span>
        <span class="ml-2">{{ userName }} ({{ userEmail }})</span>
        <template v-if="adminName">
          <span class="ml-3 opacity-80">|</span>
          <span class="ml-3">{{ originalAdminLabel }}: {{ adminName }}</span>
        </template>
      </div>
    </div>

    <button
      @click="handleStopImpersonating"
      :disabled="loading"
      class="px-4 py-1.5 bg-white text-warning-700 hover:bg-warning-50 rounded font-medium text-sm transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
    >
      <Icon v-if="loading" name="loading" :size="16" class="animate-spin" />
      <span>{{ loading ? stoppingText : stopButtonText }}</span>
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  // Required: Whether the user is currently impersonating
  isImpersonating: {
    type: Boolean,
    required: true
  },
  // User being impersonated
  userName: {
    type: String,
    default: ''
  },
  userEmail: {
    type: String,
    default: ''
  },
  // Original admin info
  adminName: {
    type: String,
    default: ''
  },
  // Callback to stop impersonating
  onStopImpersonating: {
    type: Function,
    default: null
  },
  // Labels (for i18n)
  impersonatingLabel: {
    type: String,
    default: 'Impersonating:'
  },
  originalAdminLabel: {
    type: String,
    default: 'Original Admin'
  },
  stopButtonText: {
    type: String,
    default: 'Stop Impersonating'
  },
  stoppingText: {
    type: String,
    default: 'Stopping...'
  }
})

const emit = defineEmits(['stop-impersonating'])

const loading = ref(false)

const handleStopImpersonating = async () => {
  loading.value = true
  try {
    if (props.onStopImpersonating) {
      await props.onStopImpersonating()
    }
    emit('stop-impersonating')
  } catch (error) {
    console.error('Failed to stop impersonating:', error)
  } finally {
    loading.value = false
  }
}
</script>
