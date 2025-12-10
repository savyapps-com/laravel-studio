<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <!-- Modal -->
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md transform transition-all">
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-title">{{ title }}</h3>
            <button
              @click="handleCancel"
              class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="p-4">
            <slot></slot>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 dark:border-gray-700">
            <button
              @click="handleCancel"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
            >
              {{ cancelLabel }}
            </button>
            <button
              @click="handleConfirm"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Confirm'
  },
  confirmLabel: {
    type: String,
    default: 'Confirm'
  },
  cancelLabel: {
    type: String,
    default: 'Cancel'
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:open', 'confirm', 'cancel'])

function handleConfirm() {
  emit('confirm')
}

function handleCancel() {
  emit('cancel')
  emit('update:open', false)
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    handleCancel()
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
}
</style>
