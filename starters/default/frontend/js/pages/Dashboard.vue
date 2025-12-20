<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-title">Dashboard</h1>
        <p class="text-subtitle mt-1">Welcome to your admin dashboard</p>
      </div>
      <button
        v-if="hasCards"
        @click="refreshAllCards"
        class="btn btn-secondary"
        :disabled="loading"
      >
        <Icon name="refresh" :size="16" class="mr-2" :class="{ 'animate-spin': loading }" />
        Refresh
      </button>
    </div>

    <!-- Resource Cards from Backend -->
    <CardGrid
      v-if="hasCards"
      :cards="cards"
      :loading="loading"
      @refresh="refreshCard"
    />

    <!-- Fallback Stats (when no backend cards configured) -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard
        v-for="stat in defaultStats"
        :key="stat.title"
        :title="stat.title"
        :value="stat.value"
        :icon="stat.icon"
        :variant="stat.variant"
        :trend="stat.trend"
        :prefix="stat.prefix"
      />
    </div>

    <!-- Recent Activity Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-title">Recent Activity</h2>
          <router-link
            :to="{ name: 'admin.activity' }"
            class="text-sm text-primary-500 hover:text-primary-600"
          >
            View all
          </router-link>
        </div>
        <ActivityTimeline
          :activities="activities"
          :loading="activitiesLoading"
          :compact="true"
          :limit="5"
        />
      </div>

      <!-- Quick Actions -->
      <div class="card p-6">
        <h2 class="text-lg font-semibold text-title mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
          <router-link
            :to="{ name: 'admin.users', query: { action: 'create' } }"
            class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
              <Icon name="user-plus" :size="20" class="text-primary-600 dark:text-primary-400" />
            </div>
            <span class="text-sm font-medium text-title">Add User</span>
          </router-link>
          <router-link
            :to="{ name: 'admin.users' }"
            class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <Icon name="users" :size="20" class="text-blue-600 dark:text-blue-400" />
            </div>
            <span class="text-sm font-medium text-title">View Users</span>
          </router-link>
          <router-link
            :to="{ name: 'admin.roles' }"
            class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
              <Icon name="shield" :size="20" class="text-purple-600 dark:text-purple-400" />
            </div>
            <span class="text-sm font-medium text-title">Manage Roles</span>
          </router-link>
          <router-link
            :to="{ name: 'admin.settings' }"
            class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
              <Icon name="settings" :size="20" class="text-gray-600 dark:text-gray-400" />
            </div>
            <span class="text-sm font-medium text-title">Settings</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { CardGrid, ActivityTimeline, useCards, activityService, Icon, StatCard } from 'laravel-studio'

// Cards from backend
const { cards, loading, hasCards, refreshCard, refreshAllCards, loadCards } = useCards({
  panel: 'admin',
  autoLoad: true
})

// Activity feed
const activities = ref([])
const activitiesLoading = ref(true)

const loadActivities = async () => {
  try {
    activitiesLoading.value = true
    const response = await activityService.getActivities({ limit: 5 })
    activities.value = response.activities || []
  } catch (error) {
    console.error('Failed to load activities:', error)
  } finally {
    activitiesLoading.value = false
  }
}

// Default stats for fallback when no backend cards configured
const defaultStats = ref([
  {
    title: 'Total Users',
    value: 0,
    icon: 'users',
    variant: 'blue',
    trend: { value: 0, isPositive: true, period: 'month' }
  },
  {
    title: 'Active Sessions',
    value: 0,
    icon: 'activity',
    variant: 'green',
    trend: { value: 0, isPositive: true, period: 'day' }
  },
  {
    title: 'Roles',
    value: 0,
    icon: 'shield',
    variant: 'purple'
  },
  {
    title: 'System Health',
    value: 'OK',
    icon: 'check-circle',
    variant: 'teal'
  }
])

onMounted(() => {
  loadActivities()
})
</script>
