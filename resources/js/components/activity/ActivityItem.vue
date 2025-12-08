<template>
  <div class="relative flex gap-4 pl-8">
    <!-- Timeline dot -->
    <div
      class="absolute left-2.5 top-1 h-3 w-3 rounded-full border-2 border-white"
      :class="eventDotColor"
    ></div>

    <!-- Content -->
    <div class="flex-1 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-2">
          <!-- Event Badge -->
          <span
            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
            :class="eventBadgeColor"
          >
            {{ eventLabel }}
          </span>

          <!-- Subject -->
          <span v-if="showSubject && activity.subject_type" class="text-sm text-gray-600">
            {{ subjectLabel }}
            <span v-if="activity.subject_id" class="text-gray-400">#{{ activity.subject_id }}</span>
          </span>
        </div>

        <!-- Time -->
        <time class="text-xs text-gray-400" :datetime="activity.created_at">
          {{ formattedTime }}
        </time>
      </div>

      <!-- Description -->
      <p v-if="activity.description" class="mt-2 text-sm text-gray-700">
        {{ activity.description }}
      </p>

      <!-- Causer -->
      <div v-if="showCauser && activity.causer" class="mt-2 flex items-center gap-2">
        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-600">
          {{ causerInitials }}
        </div>
        <span class="text-sm text-gray-600">{{ activity.causer.name }}</span>
      </div>

      <!-- Changes Preview -->
      <div v-if="hasChanges" class="mt-3">
        <button
          class="text-sm text-primary-600 hover:text-primary-700"
          @click="showChanges = !showChanges"
        >
          {{ showChanges ? 'Hide changes' : 'Show changes' }}
          <span class="ml-1">{{ showChanges ? '▲' : '▼' }}</span>
        </button>

        <ActivityDiff
          v-if="showChanges"
          :activity="activity"
          class="mt-2"
        />
      </div>

      <!-- View Details Button -->
      <div v-if="hasChanges || activity.ip_address" class="mt-3 flex justify-end">
        <button
          class="text-xs text-gray-500 hover:text-gray-700"
          @click="$emit('view-details', activity)"
        >
          View details
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
  },
  showSubject: {
    type: Boolean,
    default: true
  },
  showCauser: {
    type: Boolean,
    default: true
  }
})

defineEmits(['view-details'])

const showChanges = ref(false)

const eventLabel = computed(() => activityService.getEventLabel(props.activity.event))

const eventBadgeColor = computed(() => activityService.getEventColor(props.activity.event))

const eventDotColor = computed(() => {
  const colors = {
    created: 'bg-green-500',
    updated: 'bg-blue-500',
    deleted: 'bg-red-500',
    restored: 'bg-purple-500',
    viewed: 'bg-gray-400',
    login: 'bg-cyan-500',
    logout: 'bg-orange-500'
  }
  return colors[props.activity.event] || 'bg-gray-400'
})

const subjectLabel = computed(() => {
  if (!props.activity.subject_type) return ''
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

const formattedTime = computed(() => {
  if (!props.activity.created_at) return ''
  const date = new Date(props.activity.created_at)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`

  return date.toLocaleDateString()
})

const hasChanges = computed(() => {
  const properties = props.activity.properties
  return properties && (properties.old || properties.attributes)
})
</script>
