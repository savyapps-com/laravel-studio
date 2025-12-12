<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        class="fixed inset-0 overflow-y-auto"
        style="z-index: 9999;"
        @click.self="$emit('close')"
      >
        <!-- Backdrop with blur -->
        <div class="fixed inset-0 bg-gray-900/60 dark:bg-black/70 backdrop-blur-sm" @click="$emit('close')" />

        <!-- Modal Container -->
        <div class="fixed inset-0 flex items-start justify-center p-0 sm:p-4 sm:pt-[10vh]">
          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div class="relative w-full h-full sm:h-auto sm:max-w-2xl sm:rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-200 dark:ring-gray-700/50 flex flex-col overflow-hidden">
              <!-- Header -->
              <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700/50">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activity Details</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">View complete activity information</p>
                  </div>
                </div>
                <button
                  class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  @click="$emit('close')"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 space-y-6">
                <!-- Basic Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Event -->
                  <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Event</label>
                    <div class="mt-2">
                      <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium"
                        :class="eventBadgeColor"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        {{ eventLabel }}
                      </span>
                    </div>
                  </div>

                  <!-- Log Name -->
                  <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Log</label>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ activity.log_name || 'default' }}</p>
                  </div>

                  <!-- Subject -->
                  <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Subject</label>
                    <div class="mt-2 flex items-center gap-2">
                      <span class="text-sm font-medium text-gray-900 dark:text-white">{{ subjectLabel }}</span>
                      <span v-if="activity.subject_id" class="px-2 py-0.5 rounded-md bg-gray-200 dark:bg-gray-700 text-xs font-mono text-gray-600 dark:text-gray-400">
                        #{{ activity.subject_id }}
                      </span>
                    </div>
                  </div>

                  <!-- Time -->
                  <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Time</label>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ formattedDateTime }}</p>
                  </div>
                </div>

                <!-- Description -->
                <div v-if="activity.description" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                  <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Description</label>
                  <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ activity.description }}</p>
                </div>

                <!-- Causer -->
                <div v-if="activity.causer" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                  <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Performed By</label>
                  <div class="mt-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-secondary-500 to-secondary-600 flex items-center justify-center text-white font-semibold text-sm shadow-lg shadow-secondary-500/20">
                      {{ causerInitials }}
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.causer.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ activity.causer.email }}</p>
                    </div>
                  </div>
                </div>

                <!-- Changes -->
                <div v-if="hasChanges">
                  <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </div>
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Changes</label>
                  </div>
                  <ActivityDiff :activity="activity" :max-fields="999" />
                </div>

                <!-- Metadata -->
                <div v-if="activity.ip_address || activity.user_agent || activity.batch_uuid" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                  <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Metadata</label>
                  <dl class="mt-3 space-y-3">
                    <div v-if="activity.ip_address" class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                      </div>
                      <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">IP Address</dt>
                        <dd class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ activity.ip_address }}</dd>
                      </div>
                    </div>
                    <div v-if="activity.user_agent" class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">User Agent</dt>
                        <dd class="text-sm text-gray-900 dark:text-white break-all">{{ activity.user_agent }}</dd>
                      </div>
                    </div>
                    <div v-if="activity.batch_uuid" class="flex items-start gap-3">
                      <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                      </div>
                      <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Batch ID</dt>
                        <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ activity.batch_uuid }}</dd>
                      </div>
                    </div>
                  </dl>
                </div>

                <!-- Raw Properties -->
                <div v-if="activity.properties && Object.keys(activity.properties).length" class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                  <button
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/50 text-left hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    @click="showRaw = !showRaw"
                  >
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Raw Properties</span>
                    <svg
                      class="w-4 h-4 text-gray-400 transition-transform duration-200"
                      :class="{ 'rotate-180': showRaw }"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                  >
                    <pre
                      v-if="showRaw"
                      class="max-h-60 overflow-auto p-4 text-xs font-mono text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/30"
                    >{{ JSON.stringify(activity.properties, null, 2) }}</pre>
                  </Transition>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end px-4 sm:px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700/50">
                <button
                  class="px-5 py-2.5 rounded-xl text-sm font-medium bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                  @click="$emit('close')"
                >
                  Close
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
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

const eventBadgeColor = computed(() => {
  const colors = {
    created: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
    updated: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    deleted: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
    restored: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    login: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300',
    logout: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
  }
  return colors[props.activity.event] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
})

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
