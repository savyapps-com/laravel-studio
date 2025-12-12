<template>
  <div class="activity-timeline">
    <!-- Filters -->
    <div v-if="showFilters" class="mb-6 flex flex-col sm:flex-row flex-wrap gap-3">
      <div class="relative flex-1 sm:flex-none sm:min-w-[180px]">
        <select
          v-if="filterOptions.log_names?.length"
          v-model="filters.log_name"
          class="w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 pr-10 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-colors"
        >
          <option value="">All Logs</option>
          <option v-for="log in filterOptions.log_names" :key="log" :value="log">
            {{ log }}
          </option>
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
          <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      <div class="relative flex-1 sm:flex-none sm:min-w-[180px]">
        <select
          v-if="filterOptions.event_types?.length"
          v-model="filters.event"
          class="w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 pr-10 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-colors"
        >
          <option value="">All Events</option>
          <option v-for="event in filterOptions.event_types" :key="event" :value="event">
            {{ getEventLabel(event) }}
          </option>
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
          <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      <div class="relative flex-1 sm:min-w-[250px]">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
          <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search activities..."
          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-colors"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !activities.length" class="flex flex-col items-center justify-center py-16">
      <div class="w-12 h-12 border-3 border-primary-500/30 border-t-primary-500 rounded-full animate-spin"></div>
      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Loading activities...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="activities.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
      <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="text-base font-medium text-gray-900 dark:text-white">No activities found</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Activities will appear here when actions are performed
      </p>
    </div>

    <!-- Timeline -->
    <div v-else class="relative">
      <!-- Timeline line -->
      <div class="absolute left-[18px] sm:left-5 top-0 bottom-0 w-0.5 bg-gradient-to-b from-primary-500/50 via-gray-200 dark:via-gray-700 to-transparent"></div>

      <!-- Activity Items -->
      <div class="space-y-4">
        <ActivityItem
          v-for="activity in activities"
          :key="activity.id"
          :activity="activity"
          :show-subject="showSubject"
          :show-causer="showCauser"
          :compact="compact"
          @view-details="handleViewDetails"
        />
      </div>

      <!-- Loading more indicator -->
      <div v-if="loading" class="flex justify-center py-4">
        <div class="w-6 h-6 border-2 border-primary-500/30 border-t-primary-500 rounded-full animate-spin"></div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
      <p class="text-sm text-gray-500 dark:text-gray-400 order-2 sm:order-1">
        Showing <span class="font-medium text-gray-900 dark:text-white">{{ (meta.current_page - 1) * meta.per_page + 1 }}</span>
        to <span class="font-medium text-gray-900 dark:text-white">{{ Math.min(meta.current_page * meta.per_page, meta.total) }}</span>
        of <span class="font-medium text-gray-900 dark:text-white">{{ meta.total }}</span> activities
      </p>

      <div class="flex items-center gap-2 order-1 sm:order-2">
        <button
          :disabled="meta.current_page <= 1"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          @click="loadPage(meta.current_page - 1)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Previous
        </button>

        <div class="hidden sm:flex items-center gap-1">
          <template v-for="page in visiblePages" :key="page">
            <button
              v-if="page !== '...'"
              :class="[
                'w-10 h-10 text-sm font-medium rounded-xl transition-colors',
                page === meta.current_page
                  ? 'bg-primary-500 text-white'
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
              ]"
              @click="loadPage(page)"
            >
              {{ page }}
            </button>
            <span v-else class="px-2 text-gray-400">...</span>
          </template>
        </div>

        <button
          :disabled="meta.current_page >= meta.last_page"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          @click="loadPage(meta.current_page + 1)"
        >
          Next
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Activity Details Modal -->
    <ActivityDetailsModal
      v-if="selectedActivity"
      :activity="selectedActivity"
      @close="selectedActivity = null"
    />
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted, onUnmounted } from 'vue'
import ActivityItem from './ActivityItem.vue'
import ActivityDetailsModal from './ActivityDetailsModal.vue'
import activityService from '../../services/activityService'

const props = defineProps({
  subjectType: {
    type: String,
    default: null
  },
  subjectId: {
    type: [Number, String],
    default: null
  },
  showFilters: {
    type: Boolean,
    default: true
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
  },
  perPage: {
    type: Number,
    default: 15
  },
  limit: {
    type: Number,
    default: null
  },
  autoRefresh: {
    type: Number,
    default: 0
  }
})

const loading = ref(false)
const activities = ref([])
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: props.perPage,
  total: 0
})
const filterOptions = ref({
  log_names: [],
  event_types: [],
  subject_types: []
})
const filters = reactive({
  log_name: '',
  event: '',
  search: '',
  per_page: props.perPage
})
const selectedActivity = ref(null)
let refreshInterval = null

const getEventLabel = (event) => activityService.getEventLabel(event)

const visiblePages = computed(() => {
  const current = meta.value.current_page
  const last = meta.value.last_page
  const pages = []

  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')

    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)

    for (let i = start; i <= end; i++) pages.push(i)

    if (current < last - 2) pages.push('...')
    pages.push(last)
  }

  return pages
})

const loadActivities = async (page = 1) => {
  loading.value = true
  try {
    const params = { ...filters, page }
    if (props.limit) params.limit = props.limit

    let response
    if (props.subjectType && props.subjectId) {
      response = await activityService.getSubjectActivities(
        props.subjectType,
        props.subjectId,
        params
      )
    } else {
      response = await activityService.getActivities(params)
    }

    activities.value = response.data || response.activities || []
    if (response.meta) {
      meta.value = response.meta
    }
  } catch (error) {
    console.error('Failed to load activities:', error)
  } finally {
    loading.value = false
  }
}

const loadFilterOptions = async () => {
  try {
    filterOptions.value = await activityService.getFilterOptions()
  } catch (error) {
    console.error('Failed to load filter options:', error)
  }
}

const loadPage = (page) => {
  if (page !== '...') {
    loadActivities(page)
  }
}

const handleViewDetails = (activity) => {
  selectedActivity.value = activity
}

// Watch for filter changes with debounce
let filterTimeout = null
watch(filters, () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    loadActivities(1)
  }, 300)
}, { deep: true })

// Setup auto-refresh
const setupAutoRefresh = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
  if (props.autoRefresh > 0) {
    refreshInterval = setInterval(() => {
      loadActivities(meta.value.current_page)
    }, props.autoRefresh * 1000)
  }
}

onMounted(() => {
  if (props.showFilters) {
    loadFilterOptions()
  }
  loadActivities()
  setupAutoRefresh()
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
  if (filterTimeout) {
    clearTimeout(filterTimeout)
  }
})

// Expose loadActivities for parent components
defineExpose({
  loadActivities
})
</script>
