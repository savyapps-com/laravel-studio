<template>
  <div class="space-y-4">
    <!-- Logout Other Sessions -->
    <div class="flex items-start justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
      <div class="flex-1">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ logoutOthersTitle }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ logoutOthersDescription }}
        </p>
      </div>
      <button
        @click="$emit('logout-others')"
        :disabled="isLoggingOut"
        class="ml-4 px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
      >
        <span v-if="isLoggingOut">{{ loggingOutText }}</span>
        <span v-else>{{ logoutOthersButtonText }}</span>
      </button>
    </div>

    <!-- Logout All Sessions -->
    <div class="flex items-start justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-900/10">
      <div class="flex-1">
        <h3 class="text-sm font-medium text-red-900 dark:text-red-400">{{ logoutAllTitle }}</h3>
        <p class="mt-1 text-sm text-red-700 dark:text-red-500">
          {{ logoutAllDescription }}
        </p>
      </div>
      <button
        @click="$emit('logout-all')"
        :disabled="isLoggingOut"
        class="ml-4 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
      >
        <span v-if="isLoggingOut">{{ loggingOutText }}</span>
        <span v-else>{{ logoutAllButtonText }}</span>
      </button>
    </div>

    <!-- Session Success Message -->
    <div v-if="successMessage" class="auth-success bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
      <p class="text-sm font-medium text-green-600 dark:text-green-400">{{ successMessage }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isLoggingOut: {
    type: Boolean,
    default: false,
  },
  successMessage: {
    type: String,
    default: '',
  },
  // Customizable labels
  logoutOthersTitle: {
    type: String,
    default: 'Logout Other Sessions',
  },
  logoutOthersDescription: {
    type: String,
    default: 'Sign out from all other devices while keeping this session active',
  },
  logoutOthersButtonText: {
    type: String,
    default: 'Logout Others',
  },
  logoutAllTitle: {
    type: String,
    default: 'Logout All Sessions',
  },
  logoutAllDescription: {
    type: String,
    default: 'Sign out from all devices including this one. You will be redirected to login.',
  },
  logoutAllButtonText: {
    type: String,
    default: 'Logout All',
  },
  loggingOutText: {
    type: String,
    default: 'Logging out...',
  },
})

defineEmits(['logout-others', 'logout-all'])
</script>
