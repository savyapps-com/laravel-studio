<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-title">Activity Log</h1>
        <p class="text-subtitle mt-1">View all system activity and changes</p>
      </div>
      <button
        @click="refresh"
        class="btn btn-secondary"
        :disabled="loading"
      >
        <Icon name="refresh" :size="16" class="mr-2" :class="{ 'animate-spin': loading }" />
        Refresh
      </button>
    </div>

    <!-- Activity Timeline -->
    <div class="card p-6">
      <ActivityTimeline
        ref="timelineRef"
        :show-filters="true"
        :show-subject="true"
        :show-causer="true"
        :per-page="20"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ActivityTimeline } from '@core/index'
import Icon from '@/components/common/Icon.vue'

const timelineRef = ref(null)
const loading = ref(false)

const refresh = async () => {
  loading.value = true
  try {
    // Reload the timeline if it has an exposed method
    if (timelineRef.value?.loadActivities) {
      await timelineRef.value.loadActivities()
    } else {
      // Fallback: just reload the page component
      window.location.reload()
    }
  } finally {
    loading.value = false
  }
}
</script>
