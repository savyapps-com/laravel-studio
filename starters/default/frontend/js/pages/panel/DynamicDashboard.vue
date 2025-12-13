<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-title">Dashboard</h1>
        <p class="text-subtitle mt-1">Welcome to {{ panelLabel }}</p>
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

    <!-- Fallback Stats -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard
        v-for="stat in defaultStats"
        :key="stat.title"
        :title="stat.title"
        :value="stat.value"
        :icon="stat.icon"
        :variant="stat.variant"
      />
    </div>

    <!-- Quick Actions based on panel resources -->
    <div v-if="resources.length > 0" class="card p-6">
      <h2 class="text-lg font-semibold text-title mb-4">Quick Actions</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <router-link
          v-for="resource in resources.slice(0, 4)"
          :key="resource"
          :to="{ name: 'panel.resource', params: { panel: currentPanel, resource } }"
          class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
            <Icon name="folder" :size="20" class="text-primary-600 dark:text-primary-400" />
          </div>
          <span class="text-sm font-medium text-title capitalize">{{ resource }}</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { CardGrid, useCards } from '@core/index'
import Icon from '@/components/common/Icon.vue'
import StatCard from '@/components/stats/StatCard.vue'
import { panelService } from 'laravel-studio'

const route = useRoute()

// Get current panel from route
const currentPanel = computed(() => route.params.panel)
const panelLabel = computed(() => currentPanel.value?.charAt(0).toUpperCase() + currentPanel.value?.slice(1) + ' Panel')

// Panel resources
const resources = ref([])

// Cards from backend
const { cards, loading, hasCards, refreshCard, refreshAllCards } = useCards({
  panel: currentPanel.value,
  autoLoad: true
})

// Default stats for fallback
const defaultStats = ref([
  {
    title: 'Welcome',
    value: 'Hello!',
    icon: 'smile',
    variant: 'blue'
  },
  {
    title: 'Status',
    value: 'Active',
    icon: 'check-circle',
    variant: 'green'
  }
])

// Load panel resources
async function loadPanelResources() {
  try {
    const response = await panelService.getPanelResources(currentPanel.value)
    resources.value = response.resources?.map(r => r.key) || []
  } catch (error) {
    console.error('Failed to load panel resources:', error)
  }
}

onMounted(() => {
  loadPanelResources()
})
</script>
