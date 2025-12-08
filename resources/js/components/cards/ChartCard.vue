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

    <!-- Chart Area -->
    <div
      :style="{ height: `${card.chartHeight}px` }"
      class="relative"
    >
      <svg
        class="w-full h-full"
        :viewBox="`0 0 ${chartWidth} ${card.chartHeight}`"
        preserveAspectRatio="none"
      >
        <!-- Grid Lines -->
        <g v-if="card.showGrid" class="text-gray-200">
          <line
            v-for="(y, i) in gridLines"
            :key="i"
            :x1="padding.left"
            :y1="y"
            :x2="chartWidth - padding.right"
            :y2="y"
            stroke="currentColor"
            stroke-width="1"
            stroke-dasharray="4"
          />
        </g>

        <!-- Area fills (for area chart) -->
        <g v-if="card.fill">
          <defs>
            <linearGradient
              v-for="series in chartSeries"
              :key="`gradient-${series.key}`"
              :id="`area-gradient-${card.key}-${series.key}`"
              x1="0%"
              y1="0%"
              x2="0%"
              y2="100%"
            >
              <stop offset="0%" :style="{ stopColor: getColor(series.color), stopOpacity: 0.3 }" />
              <stop offset="100%" :style="{ stopColor: getColor(series.color), stopOpacity: 0 }" />
            </linearGradient>
          </defs>
          <path
            v-for="series in chartSeries"
            :key="`area-${series.key}`"
            :d="getAreaPath(series)"
            :fill="`url(#area-gradient-${card.key}-${series.key})`"
          />
        </g>

        <!-- Bar Chart -->
        <g v-if="card.chartType === 'bar'">
          <rect
            v-for="(bar, i) in barData"
            :key="i"
            :x="bar.x"
            :y="bar.y"
            :width="bar.width"
            :height="bar.height"
            :fill="getColor(bar.color)"
            rx="2"
            class="transition-opacity hover:opacity-80"
          />
        </g>

        <!-- Line/Area Chart -->
        <g v-else>
          <path
            v-for="series in chartSeries"
            :key="`line-${series.key}`"
            :d="getLinePath(series)"
            fill="none"
            :stroke="getColor(series.color)"
            stroke-width="2"
            :stroke-linecap="card.smooth ? 'round' : 'square'"
            :stroke-linejoin="card.smooth ? 'round' : 'miter'"
          />

          <!-- Data points -->
          <circle
            v-for="(point, i) in getAllPoints"
            :key="i"
            :cx="point.x"
            :cy="point.y"
            r="4"
            :fill="getColor(point.color)"
            class="opacity-0 hover:opacity-100 transition-opacity"
          />
        </g>
      </svg>

      <!-- X-axis labels -->
      <div class="flex justify-between mt-2 text-xs text-gray-500">
        <span v-for="(label, i) in xLabels" :key="i" class="truncate px-1">
          {{ label }}
        </span>
      </div>
    </div>

    <!-- Legend -->
    <div v-if="card.showLegend && chartSeries.length > 1" class="flex flex-wrap gap-4 mt-4">
      <div
        v-for="series in chartSeries"
        :key="series.key"
        class="flex items-center text-sm"
      >
        <span
          class="w-3 h-3 rounded-full mr-2"
          :style="{ backgroundColor: getColor(series.color) }"
        />
        <span class="text-gray-600">{{ series.label }}</span>
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

const chartWidth = 400
const padding = { top: 10, right: 10, bottom: 10, left: 10 }

const colorClasses = computed(() => {
  return cardService.getColorClasses(props.card.color)
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

const chartSeries = computed(() => {
  return props.card.data?.series || []
})

const labels = computed(() => {
  return props.card.data?.labels || []
})

const xLabels = computed(() => {
  // Show max 6 labels
  const all = labels.value
  if (all.length <= 6) return all

  const step = Math.ceil(all.length / 6)
  return all.filter((_, i) => i % step === 0 || i === all.length - 1)
})

const gridLines = computed(() => {
  const count = 4
  const height = props.card.chartHeight - padding.top - padding.bottom
  const lines = []

  for (let i = 0; i <= count; i++) {
    lines.push(padding.top + (height / count) * i)
  }

  return lines
})

const getDataRange = () => {
  let min = Infinity
  let max = -Infinity

  chartSeries.value.forEach(series => {
    series.data.forEach(value => {
      if (value < min) min = value
      if (value > max) max = value
    })
  })

  // Add some padding
  const range = max - min || 1
  return {
    min: Math.max(0, min - range * 0.1),
    max: max + range * 0.1
  }
}

const scaleY = (value) => {
  const { min, max } = getDataRange()
  const height = props.card.chartHeight - padding.top - padding.bottom
  return padding.top + height - ((value - min) / (max - min)) * height
}

const scaleX = (index) => {
  const count = labels.value.length
  const width = chartWidth - padding.left - padding.right
  return padding.left + (index / Math.max(1, count - 1)) * width
}

const getLinePath = (series) => {
  if (!series.data || series.data.length === 0) return ''

  return series.data
    .map((value, i) => {
      const x = scaleX(i)
      const y = scaleY(value)
      return `${i === 0 ? 'M' : 'L'} ${x} ${y}`
    })
    .join(' ')
}

const getAreaPath = (series) => {
  if (!series.data || series.data.length === 0) return ''

  const linePath = getLinePath(series)
  const lastX = scaleX(series.data.length - 1)
  const firstX = scaleX(0)
  const bottom = props.card.chartHeight - padding.bottom

  return `${linePath} L ${lastX} ${bottom} L ${firstX} ${bottom} Z`
}

const getAllPoints = computed(() => {
  const points = []

  chartSeries.value.forEach(series => {
    series.data.forEach((value, i) => {
      points.push({
        x: scaleX(i),
        y: scaleY(value),
        color: series.color,
        value
      })
    })
  })

  return points
})

const barData = computed(() => {
  if (props.card.chartType !== 'bar') return []

  const bars = []
  const seriesCount = chartSeries.value.length
  const groupWidth = (chartWidth - padding.left - padding.right) / labels.value.length
  const barWidth = (groupWidth * 0.8) / seriesCount
  const gap = groupWidth * 0.1

  chartSeries.value.forEach((series, si) => {
    series.data.forEach((value, i) => {
      const x = padding.left + i * groupWidth + gap + si * barWidth
      const y = scaleY(value)
      const height = props.card.chartHeight - padding.bottom - y

      bars.push({
        x,
        y,
        width: barWidth - 2,
        height: Math.max(0, height),
        color: series.color,
        value
      })
    })
  })

  return bars
})

const handleClick = () => {
  if (props.card.link) {
    window.location.href = props.card.link
  }
  emit('click', props.card)
}
</script>
