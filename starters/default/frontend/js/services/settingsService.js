/**
 * Settings Service
 * Handles all API calls related to settings
 */


export const settingsService = {
  // ============================================
  // User Settings
  // ============================================

  /**
   * Get all current user's settings
   * @param {string} group - Optional group filter (appearance, notifications, etc.)
   * @returns {Promise}
   */
  async getUserSettings(group = null) {
    const params = group ? { group } : {}
    const response = await window.axios.get('/api/user/settings', { params })
    return response.data
  },

  /**
   * Get a specific user setting by key
   * @param {string} key - Setting key
   * @returns {Promise}
   */
  async getUserSetting(key) {
    const response = await window.axios.get(`/api/user/settings/${key}`)
    return response.data
  },

  /**
   * Update multiple user settings at once
   * @param {Object} settings - Key-value pairs of settings
   * @returns {Promise}
   */
  async updateUserSettings(settings) {
    const response = await window.axios.put('/api/user/settings', { settings })
    return response.data
  },

  /**
   * Update a single user setting
   * @param {string} key - Setting key
   * @param {*} value - Setting value
   * @returns {Promise}
   */
  async updateUserSetting(key, value) {
    const response = await window.axios.put(`/api/user/settings/${key}`, { value })
    return response.data
  },

  // ============================================
  // Global Settings (Admin Only)
  // ============================================

  /**
   * Get all global settings
   * @param {string} group - Optional group filter
   * @param {string} scope - Optional scope filter (global, admin)
   * @returns {Promise}
   */
  async getGlobalSettings(group = null, scope = 'global') {
    const params = {}
    if (group) params.group = group
    if (scope) params.scope = scope
    const response = await window.axios.get('/api/settings', { params })
    return response.data
  },

  /**
   * Get a specific global setting by key
   * @param {string} key - Setting key
   * @returns {Promise}
   */
  async getGlobalSetting(key) {
    const response = await window.axios.get(`/api/settings/${key}`)
    return response.data
  },

  /**
   * Create or update a global setting
   * @param {string} key - Setting key
   * @param {*} value - Setting value
   * @returns {Promise}
   */
  async createGlobalSetting(key, value) {
    const response = await window.axios.post('/api/settings', { key, value })
    return response.data
  },

  /**
   * Update a global setting
   * @param {string} key - Setting key
   * @param {*} value - Setting value
   * @returns {Promise}
   */
  async updateGlobalSetting(key, value) {
    const response = await window.axios.put(`/api/settings/${key}`, { value })
    return response.data
  },

  /**
   * Delete a global setting
   * @param {string} key - Setting key
   * @returns {Promise}
   */
  async deleteGlobalSetting(key) {
    const response = await window.axios.delete(`/api/settings/${key}`)
    return response.data
  },

  // ============================================
  // Setting Groups
  // ============================================

  /**
   * Get all setting groups with counts
   * @returns {Promise}
   */
  async getSettingGroups() {
    const response = await window.axios.get('/api/settings/groups')
    return response.data
  },

  // ============================================
  // Setting Lists (Options)
  // ============================================

  /**
   * Get predefined options for a setting
   * @param {string} key - Setting list key (themes, date_formats, etc.)
   * @returns {Promise}
   */
  async getSettingLists(key) {
    const response = await window.axios.get(`/api/settings/lists/${key}`)
    return response.data
  }
}
