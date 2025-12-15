/**
 * Laravel Studio - Activity Components
 *
 * Activity logging and timeline components.
 * Import separately when activity tracking is needed.
 *
 * Usage:
 * import { ActivityTimeline, ActivityItem } from 'laravel-studio/activity'
 */

// Activity Components
export { default as ActivityTimeline } from '../components/activity/ActivityTimeline.vue'
export { default as ActivityItem } from '../components/activity/ActivityItem.vue'
export { default as ActivityDiff } from '../components/activity/ActivityDiff.vue'
export { default as ActivityDetailsModal } from '../components/activity/ActivityDetailsModal.vue'

// Activity Service
export { activityService } from '../services/activityService.js'
