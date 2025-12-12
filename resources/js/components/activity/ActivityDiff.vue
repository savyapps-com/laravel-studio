<template>
  <div class="activity-diff rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 overflow-hidden">
    <!-- Updated Event - Show old vs new -->
    <template v-if="activity.event === 'updated' && oldValues && newValues">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-700/50">
              <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Field</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Previous</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Updated</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="field in changedFields"
              :key="field"
              class="hover:bg-white dark:hover:bg-gray-800 transition-colors"
            >
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                {{ formatFieldName(field) }}
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-medium">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                  </svg>
                  {{ formatValue(oldValues[field]) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-medium">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  {{ formatValue(newValues[field]) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Created Event - Show new values -->
    <template v-else-if="activity.event === 'created' && newValues">
      <div class="p-4">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
            <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created with values</span>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <template v-for="(value, field) in limitedNewValues" :key="field">
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 p-2 rounded-lg bg-white dark:bg-gray-800">
              <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:w-1/3">{{ formatFieldName(field) }}</dt>
              <dd class="text-sm font-medium text-gray-900 dark:text-white sm:w-2/3 truncate">{{ formatValue(value) }}</dd>
            </div>
          </template>
        </dl>
        <button
          v-if="Object.keys(newValues).length > maxFields"
          class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
          @click="showAllFields = !showAllFields"
        >
          <svg class="w-3.5 h-3.5" :class="{ 'rotate-180': showAllFields }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
          {{ showAllFields ? 'Show less' : `Show all ${Object.keys(newValues).length} fields` }}
        </button>
      </div>
    </template>

    <!-- Deleted Event - Show deleted values -->
    <template v-else-if="activity.event === 'deleted' && oldValues">
      <div class="p-4">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
            <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Deleted values</span>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <template v-for="(value, field) in limitedOldValues" :key="field">
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 p-2 rounded-lg bg-white dark:bg-gray-800">
              <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:w-1/3">{{ formatFieldName(field) }}</dt>
              <dd class="text-sm font-medium text-red-600 dark:text-red-400 line-through sm:w-2/3 truncate">{{ formatValue(value) }}</dd>
            </div>
          </template>
        </dl>
        <button
          v-if="Object.keys(oldValues).length > maxFields"
          class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
          @click="showAllFields = !showAllFields"
        >
          <svg class="w-3.5 h-3.5" :class="{ 'rotate-180': showAllFields }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
          {{ showAllFields ? 'Show less' : `Show all ${Object.keys(oldValues).length} fields` }}
        </button>
      </div>
    </template>

    <!-- Restored Event - Show restored values -->
    <template v-else-if="activity.event === 'restored' && newValues">
      <div class="p-4">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
            <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Restored values</span>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <template v-for="(value, field) in limitedNewValues" :key="field">
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 p-2 rounded-lg bg-white dark:bg-gray-800">
              <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:w-1/3">{{ formatFieldName(field) }}</dt>
              <dd class="text-sm font-medium text-purple-600 dark:text-purple-400 sm:w-2/3 truncate">{{ formatValue(value) }}</dd>
            </div>
          </template>
        </dl>
      </div>
    </template>

    <!-- No changes -->
    <template v-else>
      <div class="p-4 flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        No change details available
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  activity: {
    type: Object,
    required: true
  },
  maxFields: {
    type: Number,
    default: 5
  }
})

const showAllFields = ref(false)

const properties = computed(() => props.activity.properties || {})
const oldValues = computed(() => properties.value.old || null)
const newValues = computed(() => properties.value.attributes || null)

const changedFields = computed(() => {
  if (!oldValues.value || !newValues.value) return []
  return Object.keys(newValues.value)
})

const limitedNewValues = computed(() => {
  if (!newValues.value) return {}
  const entries = Object.entries(newValues.value)
  if (showAllFields.value || entries.length <= props.maxFields) {
    return newValues.value
  }
  return Object.fromEntries(entries.slice(0, props.maxFields))
})

const limitedOldValues = computed(() => {
  if (!oldValues.value) return {}
  const entries = Object.entries(oldValues.value)
  if (showAllFields.value || entries.length <= props.maxFields) {
    return oldValues.value
  }
  return Object.fromEntries(entries.slice(0, props.maxFields))
})

const formatFieldName = (field) => {
  return field
    .replace(/_/g, ' ')
    .replace(/([A-Z])/g, ' $1')
    .replace(/^./, str => str.toUpperCase())
    .trim()
}

const formatValue = (value) => {
  if (value === null || value === undefined) return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (typeof value === 'object') return JSON.stringify(value)
  if (String(value).length > 50) return String(value).slice(0, 50) + '...'
  return String(value)
}
</script>
