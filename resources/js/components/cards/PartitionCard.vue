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
    <div class="flex items-center justify-between mb-4">
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

    <!-- Chart and Legend Container -->
    <div :class="isBarChart ? '' : 'flex items-center'">
      <!-- Donut/Pie Chart -->
      <div
        v-if="!isBarChart"
        class="flex-shrink-0"
        :style="{ width: `${card.chartHeight}px`, height: `${card.chartHeight}px` }"
      >
        <svg :viewBox="`0 0 ${chartSize} ${chartSize}`" class="w-full h-full">
          <g v-for="(segment, i) in donutSegments" :key="i">
            <path
              :d="segment.path"
              :fill="getColor(segment.color)"
              class="transition-opacity hover:opacity-80"
            />
          </g>
          <!-- Center hole for donut -->
          <circle
            v-if="card.chartType === 'donut'"
            :cx="chartSize / 2"
            :cy="chartSize / 2"
            :r="innerRadius"
            fill="white"
          />
          <!-- Center text for donut -->
          <text
            v-if="card.chartType === 'donut'"
            :x="chartSize / 2"
            :y="chartSize / 2"
            text-anchor="middle"
            dominant-baseline="middle"
            class="text-lg font-bold fill-gray-900"
          >
            {{ card.data?.total }}
          </text>
        </svg>
      </div>

      <!-- Bar Chart -->
      <div v-else class="w-full space-y-2">
        <div
          v-for="(partition, i) in partitions"
          :key="i"
          class="flex items-center"
        >
          <div class="w-24 text-sm text-gray-600 truncate">{{ partition.label }}</div>
          <div class="flex-1 mx-2 h-4 bg-gray-100 rounded overflow-hidden">
            <div
              :style="{ width: `${partition.percentage}%`, backgroundColor: getColor(partition.color) }"
              class="h-full transition-all duration-300"
            />
          </div>
          <div class="w-16 text-sm text-right text-gray-600">
            <span v-if="card.showValues">{{ partition.value }}</span>
            <span v-if="card.showPercentages" class="text-gray-400 ml-1">
              ({{ partition.percentage }}%)
            </span>
          </div>
        </div>
      </div>

      <!-- Legend (for pie/donut) -->
      <div v-if="!isBarChart" class="flex-1 ml-4 space-y-1">
        <div
          v-for="(partition, i) in partitions"
          :key="i"
          class="flex items-center text-sm"
        >
          <span
            class="w-3 h-3 rounded-full mr-2 flex-shrink-0"
            :style="{ backgroundColor: getColor(partition.color) }"
          />
          <span class="text-gray-600 truncate flex-1">{{ partition.label }}</span>
          <span v-if="card.showValues" class="text-gray-900 font-medium ml-2">
            {{ partition.value }}
          </span>
          <span v-if="card.showPercentages" class="text-gray-400 ml-1">
            {{ partition.percentage }}%
          </span>
        </div>
      </div>
    </div>

    <!-- Help Text -->
    <div v-if="card.helpText" class="mt-3 text-xs text-gray-500">
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

const chartSize = 120
const outerRadius = 55
const innerRadius = 35

const colorClasses = computed(() => {
  return cardService.getColorClasses(props.card.color)
})

const isBarChart = computed(() => {
  return props.card.chartType === 'bar'
})

const partitions = computed(() => {
  return props.card.data?.partitions || []
})

const colorMap = {
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

const getColor = (color) => {
  return colorMap[color] || color
}

const donutSegments = computed(() => {
  if (isBarChart.value || partitions.value.length === 0) return []

  const segments = []
  let currentAngle = -90 // Start at top

  const center = chartSize / 2
  const radius = outerRadius

  partitions.value.forEach(partition => {
    const angle = (partition.percentage / 100) * 360
    const startAngle = currentAngle
    const endAngle = currentAngle + angle

    const startRad = (startAngle * Math.PI) / 180
    const endRad = (endAngle * Math.PI) / 180

    const x1 = center + radius * Math.cos(startRad)
    const y1 = center + radius * Math.sin(startRad)
    const x2 = center + radius * Math.cos(endRad)
    const y2 = center + radius * Math.sin(endRad)

    const largeArc = angle > 180 ? 1 : 0

    // For pie chart
    let path
    if (props.card.chartType === 'pie') {
      path = `M ${center} ${center} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`
    } else {
      // For donut chart
      const innerX1 = center + innerRadius * Math.cos(startRad)
      const innerY1 = center + innerRadius * Math.sin(startRad)
      const innerX2 = center + innerRadius * Math.cos(endRad)
      const innerY2 = center + innerRadius * Math.sin(endRad)

      path = `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} L ${innerX2} ${innerY2} A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${innerX1} ${innerY1} Z`
    }

    segments.push({
      path,
      color: partition.color,
      label: partition.label,
      value: partition.value,
      percentage: partition.percentage
    })

    currentAngle = endAngle
  })

  return segments
})

const handleClick = () => {
  if (props.card.link) {
    window.location.href = props.card.link
  }
  emit('click', props.card)
}
</script>
