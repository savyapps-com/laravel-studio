/**
 * Panel Service
 * Handles all API calls for multi-panel system management
 * Works with the panel API endpoints defined in routes/api.php
 */

export const panelService = {
  /**
   * Get all panels accessible to the current user
   * @returns {Promise<{panels: Array, default: string|null}>}
   */
  async getAccessiblePanels() {
    const response = await window.axios.get('/api/panels')
    return response.data
  },

  /**
   * Get panel configuration including menu, resources, and features
   * @param {string} panel - Panel key (e.g., 'admin', 'user')
   * @returns {Promise<Object>} Panel configuration object
   */
  async getPanelConfig(panel) {
    const response = await window.axios.get(`/api/panels/${panel}`)
    return response.data
  },

  /**
   * Get panel menu structure
   * @param {string} panel - Panel key
   * @returns {Promise<Array>} Menu items array
   */
  async getPanelMenu(panel) {
    const response = await window.axios.get(`/api/panels/${panel}/menu`)
    return response.data
  },

  /**
   * Get resources available in a panel
   * @param {string} panel - Panel key
   * @returns {Promise<Array>} Resources array
   */
  async getPanelResources(panel) {
    const response = await window.axios.get(`/api/panels/${panel}/resources`)
    return response.data
  },

  /**
   * Switch to a different panel
   * @param {string} panel - Panel key to switch to
   * @returns {Promise<Object>} Switch result with redirect info
   */
  async switchPanel(panel) {
    const response = await window.axios.post(`/api/panels/${panel}/switch`)
    return response.data
  },

  /**
   * Get the base API URL for resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @returns {string} Full API URL for the resource
   */
  getResourceUrl(panel, resource) {
    return `/api/panels/${panel}/resources/${resource}`
  },

  /**
   * Get resource metadata for a specific panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {string} context - Context: 'index' for table view, 'form' for forms
   * @returns {Promise<Object>} Resource metadata
   */
  async getResourceMeta(panel, resource, context = 'index') {
    const response = await window.axios.get(`/api/panels/${panel}/resources/${resource}/meta`, {
      params: { context }
    })
    return response.data
  },

  /**
   * Get paginated list of resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>} Paginated data
   */
  async indexResource(panel, resource, params = {}) {
    const response = await window.axios.get(`/api/panels/${panel}/resources/${resource}`, { params })
    return response.data
  },

  /**
   * Get single resource by ID within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @returns {Promise<Object>} Resource data
   */
  async showResource(panel, resource, id) {
    const response = await window.axios.get(`/api/panels/${panel}/resources/${resource}/${id}`)
    return response.data
  },

  /**
   * Create resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Object} data - Resource data
   * @returns {Promise<Object>} Created resource
   */
  async storeResource(panel, resource, data) {
    const response = await window.axios.post(`/api/panels/${panel}/resources/${resource}`, data)
    return response.data
  },

  /**
   * Update resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @param {Object} data - Resource data
   * @returns {Promise<Object>} Updated resource
   */
  async updateResource(panel, resource, id, data) {
    const response = await window.axios.put(`/api/panels/${panel}/resources/${resource}/${id}`, data)
    return response.data
  },

  /**
   * Partially update resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @param {Object} data - Partial data
   * @returns {Promise<Object>} Updated resource
   */
  async patchResource(panel, resource, id, data) {
    const response = await window.axios.patch(`/api/panels/${panel}/resources/${resource}/${id}`, data)
    return response.data
  },

  /**
   * Delete resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @returns {Promise<Object>} Delete result
   */
  async destroyResource(panel, resource, id) {
    const response = await window.axios.delete(`/api/panels/${panel}/resources/${resource}/${id}`)
    return response.data
  },

  /**
   * Bulk delete resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Array} ids - Resource IDs
   * @returns {Promise<Object>} Bulk delete result
   */
  async bulkDeleteResources(panel, resource, ids) {
    const response = await window.axios.post(`/api/panels/${panel}/resources/${resource}/bulk/delete`, { ids })
    return response.data
  },

  /**
   * Run action on resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {string} action - Action key
   * @param {Array} ids - Resource IDs
   * @param {Object} data - Additional action data
   * @returns {Promise<Object>} Action result
   */
  async runAction(panel, resource, action, ids, data = {}) {
    const response = await window.axios.post(`/api/panels/${panel}/resources/${resource}/actions/${action}`, { ids, ...data })
    return response.data
  }
}
