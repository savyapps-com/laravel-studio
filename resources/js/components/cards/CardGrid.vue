<template>
  <div class="card-grid">
    <!-- Loading State -->
    <div v-if="loading" class="flex flex-wrap -mx-2">
      <div
        v-for="i in skeletonCount"
        :key="i"
        class="px-2 mb-4 w-full md:w-1/2 lg:w-1/4"
      >
        <div class="bg-white rounded-lg shadow p-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
          <div class="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
          <div class="h-3 bg-gray-200 rounded w-1/3"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span class="text-red-700">{{ error }}</span>
      </div>
      <button
        @click="$emit('retry')"
        class="mt-2 text-sm text-red-600 hover:text-red-800 underline"
      >
        Try again
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="cards.length === 0" class="text-center py-8 text-gray-500">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <p>{{ emptyMessage }}</p>
    </div>

    <!-- Cards Grid -->
    <div v-else class="flex flex-wrap -mx-2">
      <div
        v-for="card in cards"
        :key="card.key"
        :class="['px-2 mb-4', getWidthClass(card.width)]"
      >
        <component
          :is="getCardComponent(card.type)"
          :card="card"
          :refreshing="isRefreshing(card.key)"
          @refresh="$emit('refresh', card.key)"
          @click="$emit('card-click', card)"
        />
      </div>
    </div>

    <!-- Refresh Button -->
    <div v-if="showRefreshButton && cards.length > 0" class="mt-4 text-right">
      <button
        @click="$emit('refresh-all')"
        :disabled="loading"
        class="inline-flex items-center px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 disabled:opacity-50"
      >
        <svg
          class="w-4 h-4 mr-1"
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
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'
import cardService from '../../services/cardService'

// Lazy load card components
const ValueCard = defineAsyncComponent(() => import('./ValueCard.vue'))
const TrendCard = defineAsyncComponent(() => import('./TrendCard.vue'))
const PartitionCard = defineAsyncComponent(() => import('./PartitionCard.vue'))
const TableCard = defineAsyncComponent(() => import('./TableCard.vue'))
const ChartCard = defineAsyncComponent(() => import('./ChartCard.vue'))

const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  refreshingCards: {
    type: Set,
    default: () => new Set()
  },
  showRefreshButton: {
    type: Boolean,
    default: true
  },
  emptyMessage: {
    type: String,
    default: 'No cards to display'
  },
  skeletonCount: {
    type: Number,
    default: 4
  }
})

defineEmits(['refresh', 'refresh-all', 'retry', 'card-click'])

const cardComponents = {
  value: ValueCard,
  trend: TrendCard,
  partition: PartitionCard,
  table: TableCard,
  chart: ChartCard
}

const getCardComponent = (type) => {
  return cardComponents[type] || ValueCard
}

const getWidthClass = (width) => {
  return cardService.getWidthClass(width)
}

const isRefreshing = (cardKey) => {
  return props.refreshingCards.has(cardKey)
}
</script>

<style scoped>
.card-grid {
  @apply w-full;
}
</style>
