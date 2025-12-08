/**
 * Activity Service
 * Handles all API calls for activity/audit log management
 */

export const activityService = {
  /**
   * Get paginated list of activities
   * @param {Object} filters - Filter options
   * @returns {Promise} { data: Array, meta: Object }
   */
  async getActivities(filters = {}) {
    const response = await window.axios.get('/api/activities', { params: filters })
    return response.data
  },

  /**
   * Get a single activity by ID
   * @param {number} id - Activity ID
   * @returns {Promise} { data: Object }
   */
  async getActivity(id) {
    const response = await window.axios.get(`/api/activities/${id}`)
    return response.data
  },

  /**
   * Get recent activities
   * @param {number} limit - Number of activities to fetch
   * @param {string} logName - Optional log name filter
   * @returns {Promise} { data: Array }
   */
  async getRecentActivities(limit = 10, logName = null) {
    const params = { limit }
    if (logName) params.log_name = logName
    const response = await window.axios.get('/api/activities/recent', { params })
    return response.data
  },

  /**
   * Get activity statistics
   * @param {Object} filters - Filter options
   * @returns {Promise} { total: number, by_event: Object, by_log: Object, today: number }
   */
  async getStatistics(filters = {}) {
    const response = await window.axios.get('/api/activities/statistics', { params: filters })
    return response.data
  },

  /**
   * Get filter options for activities
   * @returns {Promise} { log_names: Array, event_types: Array, subject_types: Array }
   */
  async getFilterOptions() {
    const response = await window.axios.get('/api/activities/filter-options')
    return response.data
  },

  /**
   * Get current user's activities
   * @param {Object} filters - Filter options
   * @returns {Promise} { data: Array, meta: Object }
   */
  async getMyActivities(filters = {}) {
    const response = await window.axios.get('/api/activities/my', { params: filters })
    return response.data
  },

  /**
   * Get activities for a specific subject (model)
   * @param {string} subjectType - Model type (e.g., 'User', 'Post')
   * @param {number} subjectId - Model ID
   * @param {Object} filters - Filter options
   * @returns {Promise} { data: Array, meta: Object }
   */
  async getSubjectActivities(subjectType, subjectId, filters = {}) {
    const response = await window.axios.get(`/api/activities/subject/${subjectType}/${subjectId}`, { params: filters })
    return response.data
  },

  /**
   * Delete a single activity
   * @param {number} id - Activity ID
   * @returns {Promise} { message: string }
   */
  async deleteActivity(id) {
    const response = await window.axios.delete(`/api/activities/${id}`)
    return response.data
  },

  /**
   * Bulk delete activities
   * @param {Array} ids - Array of activity IDs
   * @returns {Promise} { message: string, affected: number }
   */
  async bulkDeleteActivities(ids) {
    const response = await window.axios.post('/api/activities/bulk/delete', { ids })
    return response.data
  },

  /**
   * Cleanup old activities
   * @param {number} days - Number of days to keep (optional)
   * @returns {Promise} { message: string, affected: number }
   */
  async cleanupActivities(days = null) {
    const params = days ? { days } : {}
    const response = await window.axios.post('/api/activities/cleanup', params)
    return response.data
  },

  /**
   * Get human-readable event label
   * @param {string} event - Event type
   * @returns {string}
   */
  getEventLabel(event) {
    const labels = {
      created: 'Created',
      updated: 'Updated',
      deleted: 'Deleted',
      restored: 'Restored',
      viewed: 'Viewed',
      exported: 'Exported',
      imported: 'Imported',
      login: 'Logged In',
      logout: 'Logged Out'
    }
    return labels[event] || event
  },

  /**
   * Get event color class
   * @param {string} event - Event type
   * @returns {string}
   */
  getEventColor(event) {
    const colors = {
      created: 'text-green-600 bg-green-100',
      updated: 'text-blue-600 bg-blue-100',
      deleted: 'text-red-600 bg-red-100',
      restored: 'text-purple-600 bg-purple-100',
      viewed: 'text-gray-600 bg-gray-100',
      login: 'text-cyan-600 bg-cyan-100',
      logout: 'text-orange-600 bg-orange-100'
    }
    return colors[event] || 'text-gray-600 bg-gray-100'
  },

  /**
   * Format activity description
   * @param {Object} activity - Activity object
   * @returns {string}
   */
  formatDescription(activity) {
    if (activity.description) {
      return activity.description
    }

    const subjectLabel = activity.subject_type
      ? activity.subject_type.split('\\').pop()
      : 'Item'
    const causerName = activity.causer?.name || 'System'

    return `${causerName} ${activity.event} ${subjectLabel} #${activity.subject_id || ''}`
  }
}

export default activityService
