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

    <!-- Table -->
    <div class="overflow-x-auto -mx-4 px-4">
      <table v-if="hasRows" class="w-full text-sm">
        <thead v-if="card.showHeaders">
          <tr class="border-b border-gray-100">
            <th
              v-for="column in columns"
              :key="column.key"
              class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2 px-2 first:pl-0 last:pr-0"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, i) in rows"
            :key="i"
            :class="[
              'border-b border-gray-50 last:border-0',
              row._url ? 'hover:bg-gray-50 cursor-pointer' : ''
            ]"
            @click.stop="handleRowClick(row)"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="py-2 px-2 first:pl-0 last:pr-0"
              :class="column.class"
            >
              <slot :name="`cell-${column.key}`" :value="row[column.key]" :row="row">
                {{ formatCellValue(row[column.key], column) }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-else class="text-center py-6 text-gray-500">
        {{ card.emptyMessage }}
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

const emit = defineEmits(['refresh', 'click', 'row-click'])

const colorClasses = computed(() => {
  return cardService.getColorClasses(props.card.color)
})

const columns = computed(() => {
  return props.card.data?.columns || []
})

const rows = computed(() => {
  return props.card.data?.rows || []
})

const hasRows = computed(() => {
  return rows.value.length > 0
})

const formatCellValue = (value, column) => {
  if (value === null || value === undefined) return '-'

  // Check for column-specific formatting
  if (column.format) {
    return cardService.formatValue(value, column.format, {
      currency: column.currency,
      decimals: column.decimals
    })
  }

  // Handle dates
  if (column.type === 'date' && value) {
    return new Date(value).toLocaleDateString()
  }

  // Handle booleans
  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No'
  }

  return value
}

const handleClick = () => {
  if (props.card.link) {
    window.location.href = props.card.link
  }
  emit('click', props.card)
}

const handleRowClick = (row) => {
  if (row._url) {
    window.location.href = row._url
  }
  emit('row-click', row)
}
</script>
