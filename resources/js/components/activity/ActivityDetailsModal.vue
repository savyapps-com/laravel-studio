<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="$emit('close')">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-auto rounded-lg bg-white shadow-xl">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-900">Activity Details</h3>
        <button
          class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
          @click="$emit('close')"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="space-y-6 p-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-medium uppercase text-gray-500">Event</label>
            <p class="mt-1">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="eventBadgeColor"
              >
                {{ eventLabel }}
              </span>
            </p>
          </div>

          <div>
            <label class="text-xs font-medium uppercase text-gray-500">Log</label>
            <p class="mt-1 text-sm text-gray-900">{{ activity.log_name }}</p>
          </div>

          <div>
            <label class="text-xs font-medium uppercase text-gray-500">Subject</label>
            <p class="mt-1 text-sm text-gray-900">
              {{ subjectLabel }}
              <span v-if="activity.subject_id" class="text-gray-400">#{{ activity.subject_id }}</span>
            </p>
          </div>

          <div>
            <label class="text-xs font-medium uppercase text-gray-500">Time</label>
            <p class="mt-1 text-sm text-gray-900">{{ formattedDateTime }}</p>
          </div>
        </div>

        <!-- Description -->
        <div v-if="activity.description">
          <label class="text-xs font-medium uppercase text-gray-500">Description</label>
          <p class="mt-1 text-sm text-gray-900">{{ activity.description }}</p>
        </div>

        <!-- Causer -->
        <div v-if="activity.causer">
          <label class="text-xs font-medium uppercase text-gray-500">Performed By</label>
          <div class="mt-1 flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-sm font-medium text-gray-600">
              {{ causerInitials }}
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900">{{ activity.causer.name }}</p>
              <p class="text-xs text-gray-500">{{ activity.causer.email }}</p>
            </div>
          </div>
        </div>

        <!-- Changes -->
        <div v-if="hasChanges">
          <label class="text-xs font-medium uppercase text-gray-500">Changes</label>
          <ActivityDiff :activity="activity" :max-fields="999" class="mt-2" />
        </div>

        <!-- Metadata -->
        <div v-if="activity.ip_address || activity.user_agent" class="border-t border-gray-200 pt-4">
          <label class="text-xs font-medium uppercase text-gray-500">Metadata</label>
          <dl class="mt-2 space-y-2 text-sm">
            <div v-if="activity.ip_address" class="flex gap-2">
              <dt class="text-gray-500">IP Address:</dt>
              <dd class="text-gray-900">{{ activity.ip_address }}</dd>
            </div>
            <div v-if="activity.user_agent" class="flex gap-2">
              <dt class="text-gray-500">User Agent:</dt>
              <dd class="text-gray-900 break-all">{{ activity.user_agent }}</dd>
            </div>
            <div v-if="activity.batch_uuid" class="flex gap-2">
              <dt class="text-gray-500">Batch ID:</dt>
              <dd class="font-mono text-xs text-gray-900">{{ activity.batch_uuid }}</dd>
            </div>
          </dl>
        </div>

        <!-- Raw Properties -->
        <div v-if="activity.properties && Object.keys(activity.properties).length">
          <button
            class="text-xs text-gray-500 hover:text-gray-700"
            @click="showRaw = !showRaw"
          >
            {{ showRaw ? 'Hide' : 'Show' }} raw properties
          </button>
          <pre
            v-if="showRaw"
            class="mt-2 max-h-60 overflow-auto rounded bg-gray-50 p-3 text-xs text-gray-700"
          >{{ JSON.stringify(activity.properties, null, 2) }}</pre>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex justify-end border-t border-gray-200 px-6 py-4">
        <button
          class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import ActivityDiff from './ActivityDiff.vue'
import activityService from '../../services/activityService'

const props = defineProps({
  activity: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

const showRaw = ref(false)

const eventLabel = computed(() => activityService.getEventLabel(props.activity.event))
const eventBadgeColor = computed(() => activityService.getEventColor(props.activity.event))

const subjectLabel = computed(() => {
  if (!props.activity.subject_type) return 'N/A'
  return props.activity.subject_type.split('\\').pop()
})

const causerInitials = computed(() => {
  if (!props.activity.causer?.name) return '?'
  return props.activity.causer.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const formattedDateTime = computed(() => {
  if (!props.activity.created_at) return ''
  return new Date(props.activity.created_at).toLocaleString()
})

const hasChanges = computed(() => {
  const properties = props.activity.properties
  return properties && (properties.old || properties.attributes)
})
</script>
