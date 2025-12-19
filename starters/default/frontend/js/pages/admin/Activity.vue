<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/25">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Log</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">View all system activity and changes</p>
        </div>
      </div>
      <button
        @click="refresh"
        :disabled="loading"
        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
      >
        <svg
          class="w-4 h-4"
          :class="{ 'animate-spin': loading }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <!-- Activity Timeline Card -->
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700/50 overflow-hidden">
      <div class="p-4 sm:p-6">
        <ActivityTimeline
          ref="timelineRef"
          :show-filters="true"
          :show-subject="true"
          :show-causer="true"
          :per-page="20"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ActivityTimeline } from 'laravel-studio'

const timelineRef = ref(null)
const loading = ref(false)

const refresh = async () => {
  loading.value = true
  try {
    if (timelineRef.value?.loadActivities) {
      await timelineRef.value.loadActivities()
    } else {
      window.location.reload()
    }
  } finally {
    loading.value = false
  }
}
</script>
