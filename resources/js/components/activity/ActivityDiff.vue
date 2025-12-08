<template>
  <div class="activity-diff rounded-md border border-gray-200 bg-gray-50 p-3">
    <!-- Updated Event - Show old vs new -->
    <template v-if="activity.event === 'updated' && oldValues && newValues">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="py-1 text-left font-medium text-gray-600">Field</th>
            <th class="py-1 text-left font-medium text-gray-600">Old Value</th>
            <th class="py-1 text-left font-medium text-gray-600">New Value</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="field in changedFields"
            :key="field"
            class="border-b border-gray-100 last:border-0"
          >
            <td class="py-2 font-medium text-gray-700">{{ formatFieldName(field) }}</td>
            <td class="py-2">
              <span class="rounded bg-red-100 px-1 text-red-700">
                {{ formatValue(oldValues[field]) }}
              </span>
            </td>
            <td class="py-2">
              <span class="rounded bg-green-100 px-1 text-green-700">
                {{ formatValue(newValues[field]) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </template>

    <!-- Created Event - Show new values -->
    <template v-else-if="activity.event === 'created' && newValues">
      <div class="space-y-2">
        <div class="text-xs font-medium uppercase text-gray-500">Created with values:</div>
        <dl class="grid grid-cols-2 gap-2">
          <template v-for="(value, field) in limitedNewValues" :key="field">
            <dt class="text-gray-600">{{ formatFieldName(field) }}:</dt>
            <dd class="font-medium text-gray-900">{{ formatValue(value) }}</dd>
          </template>
        </dl>
        <button
          v-if="Object.keys(newValues).length > maxFields"
          class="text-xs text-primary-600 hover:text-primary-700"
          @click="showAllFields = !showAllFields"
        >
          {{ showAllFields ? 'Show less' : `Show all ${Object.keys(newValues).length} fields` }}
        </button>
      </div>
    </template>

    <!-- Deleted Event - Show deleted values -->
    <template v-else-if="activity.event === 'deleted' && oldValues">
      <div class="space-y-2">
        <div class="text-xs font-medium uppercase text-gray-500">Deleted values:</div>
        <dl class="grid grid-cols-2 gap-2">
          <template v-for="(value, field) in limitedOldValues" :key="field">
            <dt class="text-gray-600">{{ formatFieldName(field) }}:</dt>
            <dd class="font-medium text-red-600 line-through">{{ formatValue(value) }}</dd>
          </template>
        </dl>
        <button
          v-if="Object.keys(oldValues).length > maxFields"
          class="text-xs text-primary-600 hover:text-primary-700"
          @click="showAllFields = !showAllFields"
        >
          {{ showAllFields ? 'Show less' : `Show all ${Object.keys(oldValues).length} fields` }}
        </button>
      </div>
    </template>

    <!-- Restored Event - Show restored values -->
    <template v-else-if="activity.event === 'restored' && newValues">
      <div class="space-y-2">
        <div class="text-xs font-medium uppercase text-gray-500">Restored values:</div>
        <dl class="grid grid-cols-2 gap-2">
          <template v-for="(value, field) in limitedNewValues" :key="field">
            <dt class="text-gray-600">{{ formatFieldName(field) }}:</dt>
            <dd class="font-medium text-green-600">{{ formatValue(value) }}</dd>
          </template>
        </dl>
      </div>
    </template>

    <!-- No changes -->
    <template v-else>
      <p class="text-sm text-gray-500">No change details available</p>
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
