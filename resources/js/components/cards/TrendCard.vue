<template>
  <div
    :class="[
      'bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4',
      card.cssClass,
      { 'cursor-pointer': card.link }
    ]"
    @click="handleClick"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center">
        <div
          v-if="card.icon"
          :class="['w-8 h-8 rounded-lg flex items-center justify-center mr-3', colorClasses.light]"
        >
          <span :class="['text-xs font-bold', colorClasses.text]">
            {{ card.icon?.charAt(0).toUpperCase() }}
          </span>
        </div>
        <h3 class="text-sm font-medium text-gray-600">{{ card.title }}</h3>
      </div>
      <button
        v-if="!refreshing"
        @click.stop="$emit('refresh')"
        class="p-1 text-gray-400 hover:text-gray-600 rounded"
        title="Refresh"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
      <svg
        v-else
        class="w-4 h-4 text-gray-400 animate-spin"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Value and Change -->
    <div class="flex items-baseline justify-between mb-3">
      <span class="text-3xl font-bold text-gray-900">
        {{ formattedValue }}
      </span>
      <div v-if="hasChange" class="flex items-center" :class="trendColorClass">
        <svg
          v-if="card.data.trend === 'up'"
          class="w-4 h-4 mr-1"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <svg
          v-else-if="card.data.trend === 'down'"
          class="w-4 h-4 mr-1"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium">
          {{ Math.abs(card.data.changePercentage) }}%
        </span>
      </div>
    </div>

    <!-- Comparison Label -->
    <div v-if="card.comparisonLabel || card.data.previousValue !== null" class="text-sm text-gray-500 mb-3">
      <span v-if="card.data.previousValue !== null">
        {{ card.comparisonLabel || 'vs' }} {{ formattedPreviousValue }}
      </span>
    </div>

    <!-- Mini Trend Chart -->
    <div
      v-if="card.showChart && hasTrendData"
      class="mt-2"
      :style="{ height: `${card.chartHeight}px` }"
    >
      <svg
        class="w-full h-full"
        :viewBox="`0 0 ${chartWidth} ${card.chartHeight}`"
        preserveAspectRatio="none"
      >
        <!-- Gradient fill -->
        <defs>
          <linearGradient :id="`gradient-${card.key}`" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" :style="{ stopColor: chartColor, stopOpacity: 0.3 }" />
            <stop offset="100%" :style="{ stopColor: chartColor, stopOpacity: 0 }" />
          </linearGradient>
        </defs>

        <!-- Area fill -->
        <path
          :d="areaPath"
          :fill="`url(#gradient-${card.key})`"
        />

        <!-- Line -->
        <path
          :d="linePath"
          fill="none"
          :stroke="chartColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />

        <!-- Data points -->
        <circle
          v-for="(point, i) in chartPoints"
          :key="i"
          :cx="point.x"
          :cy="point.y"
          r="3"
          :fill="chartColor"
          class="opacity-0 hover:opacity-100 transition-opacity"
        />
      </svg>
    </div>

    <!-- Help Text -->
    <div v-if="card.helpText" class="mt-2 text-xs text-gray-500">
      {{ card.helpText }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import cardService from '../../services/cardService'

const props = defineProps({
  card: {
    type: Object,
    required: true
  },
  refreshing: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['refresh', 'click'])

const chartWidth = 200

const colorClasses = computed(() => {
  return cardService.getColorClasses(props.card.color)
})

const chartColor = computed(() => {
  const colors = {
    blue: '#3B82F6',
    green: '#10B981',
    yellow: '#F59E0B',
    red: '#EF4444',
    purple: '#8B5CF6',
    pink: '#EC4899',
    indigo: '#6366F1',
    cyan: '#06B6D4',
    orange: '#F97316',
    teal: '#14B8A6',
    gray: '#6B7280'
  }
  return colors[props.card.color] || colors.blue
})

const formattedValue = computed(() => {
  return cardService.formatValue(props.card.data?.value, props.card.format, {
    currency: props.card.currency,
    decimals: props.card.decimals
  })
})

const formattedPreviousValue = computed(() => {
  return cardService.formatValue(props.card.data?.previousValue, props.card.format, {
    currency: props.card.currency,
    decimals: props.card.decimals
  })
})

const hasChange = computed(() => {
  return props.card.data?.trend && props.card.data?.changePercentage !== null
})

const trendColorClass = computed(() => {
  return cardService.getTrendColorClass(props.card.data?.trend)
})

const hasTrendData = computed(() => {
  const trendData = props.card.data?.trendData
  return Array.isArray(trendData) && trendData.length > 1
})

const chartPoints = computed(() => {
  const trendData = props.card.data?.trendData || []
  if (trendData.length < 2) return []

  const values = trendData.map(d => typeof d === 'object' ? d.value : d)
  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = max - min || 1

  const padding = 4
  const height = props.card.chartHeight - padding * 2

  return values.map((value, i) => ({
    x: (i / (values.length - 1)) * chartWidth,
    y: padding + height - ((value - min) / range) * height
  }))
})

const linePath = computed(() => {
  if (chartPoints.value.length < 2) return ''

  return chartPoints.value
    .map((point, i) => `${i === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
    .join(' ')
})

const areaPath = computed(() => {
  if (chartPoints.value.length < 2) return ''

  const points = chartPoints.value
  const line = points
    .map((point, i) => `${i === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
    .join(' ')

  return `${line} L ${points[points.length - 1].x} ${props.card.chartHeight} L ${points[0].x} ${props.card.chartHeight} Z`
})

const handleClick = () => {
  if (props.card.link) {
    window.location.href = props.card.link
  }
  emit('click', props.card)
}
</script>
