<template>
  <div class="activity-timeline">
    <!-- Filters -->
    <div v-if="showFilters" class="mb-4 flex flex-wrap gap-3">
      <select
        v-if="filterOptions.log_names?.length"
        v-model="filters.log_name"
        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
      >
        <option value="">All Logs</option>
        <option v-for="log in filterOptions.log_names" :key="log" :value="log">
          {{ log }}
        </option>
      </select>

      <select
        v-if="filterOptions.event_types?.length"
        v-model="filters.event"
        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
      >
        <option value="">All Events</option>
        <option v-for="event in filterOptions.event_types" :key="event" :value="event">
          {{ getEventLabel(event) }}
        </option>
      </select>

      <input
        v-model="filters.search"
        type="text"
        placeholder="Search activities..."
        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="activities.length === 0" class="py-8 text-center text-gray-500">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="mt-2">No activities found</p>
    </div>

    <!-- Timeline -->
    <div v-else class="relative">
      <!-- Timeline line -->
      <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200"></div>

      <!-- Activity Items -->
      <div class="space-y-4">
        <ActivityItem
          v-for="activity in activities"
          :key="activity.id"
          :activity="activity"
          :show-subject="showSubject"
          :show-causer="showCauser"
          @view-details="handleViewDetails"
        />
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="mt-6 flex items-center justify-between">
      <button
        :disabled="meta.current_page <= 1"
        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
        @click="loadPage(meta.current_page - 1)"
      >
        Previous
      </button>
      <span class="text-sm text-gray-700">
        Page {{ meta.current_page }} of {{ meta.last_page }}
      </span>
      <button
        :disabled="meta.current_page >= meta.last_page"
        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
        @click="loadPage(meta.current_page + 1)"
      >
        Next
      </button>
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
import { ref, reactive, watch, onMounted } from 'vue'
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
  perPage: {
    type: Number,
    default: 15
  },
  autoRefresh: {
    type: Number,
    default: 0 // seconds, 0 = disabled
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

const loadActivities = async (page = 1) => {
  loading.value = true
  try {
    const params = { ...filters, page }

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

    activities.value = response.data
    meta.value = response.meta
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
  loadActivities(page)
}

const handleViewDetails = (activity) => {
  selectedActivity.value = activity
}

// Watch for filter changes
watch(filters, () => {
  loadActivities(1)
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
  loadFilterOptions()
  loadActivities()
  setupAutoRefresh()
})
</script>

<style scoped>
.activity-timeline {
  @apply relative;
}
</style>
