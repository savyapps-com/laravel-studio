<template>
  <div class="relative flex gap-3 sm:gap-4 pl-10 sm:pl-12 group">
    <!-- Timeline dot with ring effect -->
    <div
      class="absolute left-1 sm:left-2 top-2 w-4 h-4 sm:w-5 sm:h-5 rounded-full border-[3px] border-white dark:border-gray-900 ring-2 transition-all duration-200"
      :class="eventDotClasses"
    ></div>

    <!-- Content Card -->
    <div
      :class="[
        'flex-1 rounded-xl border transition-all duration-200',
        compact
          ? 'p-3 bg-gray-50 dark:bg-gray-800/50 border-transparent'
          : 'p-4 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md dark:hover:shadow-gray-900/20'
      ]"
    >
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Event Badge -->
          <span
            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
            :class="eventBadgeClasses"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="eventDotColor"></span>
            {{ eventLabel }}
          </span>

          <!-- Subject -->
          <span v-if="showSubject && activity.subject_type" class="text-sm text-gray-600 dark:text-gray-400">
            {{ subjectLabel }}
            <span v-if="activity.subject_id" class="text-gray-400 dark:text-gray-500 font-mono text-xs">#{{ activity.subject_id }}</span>
          </span>
        </div>

        <!-- Time -->
        <time
          class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1"
          :datetime="activity.created_at"
          :title="fullDateTime"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ formattedTime }}
        </time>
      </div>

      <!-- Description -->
      <p v-if="activity.description && !compact" class="mt-2 text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
        {{ activity.description }}
      </p>

      <!-- Causer & Actions Row -->
      <div v-if="!compact" class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <!-- Causer -->
        <div v-if="showCauser && activity.causer" class="flex items-center gap-2">
          <div class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-primary-400 to-secondary-500 flex items-center justify-center text-xs font-semibold text-white shadow-sm">
            {{ causerInitials }}
          </div>
          <div class="min-w-0">
            <span class="text-sm font-medium text-gray-900 dark:text-white truncate block">{{ activity.causer.name }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
          <button
            v-if="hasChanges"
            @click="showChanges = !showChanges"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
            :class="showChanges
              ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ showChanges ? 'Hide' : 'Changes' }}
          </button>

          <button
            v-if="hasChanges || activity.ip_address"
            @click="$emit('view-details', activity)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Details
          </button>
        </div>
      </div>

      <!-- Compact Causer -->
      <div v-else-if="compact && showCauser && activity.causer" class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <span>by</span>
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ activity.causer.name }}</span>
      </div>

      <!-- Changes Preview -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <ActivityDiff
          v-if="showChanges && hasChanges"
          :activity="activity"
          class="mt-3"
        />
      </Transition>
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
  },
  compact: {
    type: Boolean,
    default: false
  }
})

defineEmits(['view-details'])

const showChanges = ref(false)

const eventLabel = computed(() => activityService.getEventLabel(props.activity.event))

const eventBadgeClasses = computed(() => {
  const colors = {
    created: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    updated: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    deleted: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
    restored: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    viewed: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    login: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300',
    logout: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300'
  }
  return colors[props.activity.event] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
})

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

const eventDotClasses = computed(() => {
  const colors = {
    created: 'bg-green-500 ring-green-500/20',
    updated: 'bg-blue-500 ring-blue-500/20',
    deleted: 'bg-red-500 ring-red-500/20',
    restored: 'bg-purple-500 ring-purple-500/20',
    viewed: 'bg-gray-400 ring-gray-400/20',
    login: 'bg-cyan-500 ring-cyan-500/20',
    logout: 'bg-orange-500 ring-orange-500/20'
  }
  return colors[props.activity.event] || 'bg-gray-400 ring-gray-400/20'
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

const fullDateTime = computed(() => {
  if (!props.activity.created_at) return ''
  return new Date(props.activity.created_at).toLocaleString()
})

const hasChanges = computed(() => {
  const properties = props.activity.properties
  return properties && (properties.old || properties.attributes)
})
</script>
