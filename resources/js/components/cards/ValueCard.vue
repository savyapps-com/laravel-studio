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
          <component :is="iconComponent" :class="['w-5 h-5', colorClasses.text]" />
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

    <!-- Value -->
    <div class="mb-2">
      <span class="text-3xl font-bold text-gray-900">
        {{ card.prefix }}{{ formattedValue }}{{ card.suffix }}
      </span>
    </div>

    <!-- Trend -->
    <div v-if="hasTrend" class="flex items-center text-sm">
      <svg
        v-if="card.data.trend === 'up'"
        class="w-4 h-4 mr-1"
        :class="trendColorClass"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
      </svg>
      <svg
        v-else-if="card.data.trend === 'down'"
        class="w-4 h-4 mr-1"
        :class="trendColorClass"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
      </svg>
      <span :class="trendColorClass">
        {{ Math.abs(card.data.trendPercentage) }}%
      </span>
      <span v-if="card.trendLabel" class="text-gray-500 ml-1">
        {{ card.trendLabel }}
      </span>
    </div>

    <!-- Help Text -->
    <div v-if="card.helpText" class="mt-2 text-xs text-gray-500">
      {{ card.helpText }}
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'
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

const colorClasses = computed(() => {
  return cardService.getColorClasses(props.card.color)
})

const formattedValue = computed(() => {
  return cardService.formatValue(props.card.data?.value, props.card.format, {
    currency: props.card.currency,
    decimals: props.card.decimals
  })
})

const hasTrend = computed(() => {
  return props.card.data?.trend && props.card.data?.trendPercentage !== null
})

const trendColorClass = computed(() => {
  return cardService.getTrendColorClass(props.card.data?.trend)
})

const iconComponent = computed(() => {
  // Return a simple placeholder icon - in production, you'd use your icon library
  return {
    render() {
      return h('span', { class: 'text-xs font-bold' }, props.card.icon?.charAt(0).toUpperCase())
    }
  }
})

const handleClick = () => {
  if (props.card.link) {
    window.location.href = props.card.link
  }
  emit('click', props.card)
}
</script>
