/**
 * Panel Service
 * Handles all API calls for panel management and panel-scoped resources
 */

export const panelService = {
  /**
   * Get all panels accessible by the current user
   * @returns {Promise} { panels: Array, default: string }
   */
  async getAccessiblePanels() {
    const response = await window.axios.get('/api/panels')
    return response.data
  },

  /**
   * Get configuration for a specific panel
   * @param {string} panel - Panel key (e.g., 'admin', 'vendor', 'user')
   * @returns {Promise}
   */
  async getPanelConfig(panel) {
    const response = await window.axios.get(`/api/panels/${panel}`)
    return response.data
  },

  /**
   * Get menu items for a specific panel
   * @param {string} panel - Panel key
   * @returns {Promise}
   */
  async getPanelMenu(panel) {
    const response = await window.axios.get(`/api/panels/${panel}/menu`)
    return response.data
  },

  /**
   * Get resources available in a specific panel
   * @param {string} panel - Panel key
   * @returns {Promise}
   */
  async getPanelResources(panel) {
    const response = await window.axios.get(`/api/panels/${panel}/resources`)
    return response.data
  },

  /**
   * Switch to a different panel
   * @param {string} panel - Panel key to switch to
   * @returns {Promise}
   */
  async switchPanel(panel) {
    const response = await window.axios.post(`/api/panels/${panel}/switch`)
    return response.data
  },

  /**
   * Get resource API base URL for a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @returns {string}
   */
  getResourceUrl(panel, resource) {
    return `/api/panels/${panel}/resources/${resource}`
  },

  // ============================================
  // Panel-Scoped Resource Operations
  // ============================================

  /**
   * Get resource metadata within a panel context
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {string} context - Context: 'index' or 'form'
   * @returns {Promise}
   */
  async getMeta(panel, resource, context = 'index') {
    const response = await window.axios.get(
      `${this.getResourceUrl(panel, resource)}/meta`,
      { params: { context } }
    )
    return response.data
  },

  /**
   * Get paginated list of resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Object} params - Query parameters
   * @returns {Promise}
   */
  async index(panel, resource, params = {}) {
    const response = await window.axios.get(
      this.getResourceUrl(panel, resource),
      { params }
    )
    return response.data
  },

  /**
   * Get single resource by ID within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @returns {Promise}
   */
  async show(panel, resource, id) {
    const response = await window.axios.get(
      `${this.getResourceUrl(panel, resource)}/${id}`
    )
    return response.data
  },

  /**
   * Create new resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Object} data - Resource data
   * @returns {Promise}
   */
  async store(panel, resource, data) {
    const response = await window.axios.post(
      this.getResourceUrl(panel, resource),
      data
    )
    return response.data
  },

  /**
   * Update existing resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @param {Object} data - Resource data
   * @returns {Promise}
   */
  async update(panel, resource, id, data) {
    const response = await window.axios.put(
      `${this.getResourceUrl(panel, resource)}/${id}`,
      data
    )
    return response.data
  },

  /**
   * Partially update existing resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @param {Object} data - Partial resource data
   * @returns {Promise}
   */
  async patch(panel, resource, id, data) {
    const response = await window.axios.patch(
      `${this.getResourceUrl(panel, resource)}/${id}`,
      data
    )
    return response.data
  },

  /**
   * Delete resource within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {number|string} id - Resource ID
   * @returns {Promise}
   */
  async destroy(panel, resource, id) {
    const response = await window.axios.delete(
      `${this.getResourceUrl(panel, resource)}/${id}`
    )
    return response.data
  },

  /**
   * Bulk delete resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Array} ids - Array of resource IDs
   * @returns {Promise}
   */
  async bulkDelete(panel, resource, ids) {
    const response = await window.axios.post(
      `${this.getResourceUrl(panel, resource)}/bulk/delete`,
      { ids }
    )
    return response.data
  },

  /**
   * Bulk update resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {Array} ids - Array of resource IDs
   * @param {Object} data - Data to update
   * @returns {Promise}
   */
  async bulkUpdate(panel, resource, ids, data) {
    const response = await window.axios.post(
      `${this.getResourceUrl(panel, resource)}/bulk/update`,
      { ids, ...data }
    )
    return response.data
  },

  /**
   * Run custom action on resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {string} action - Action key
   * @param {Array} ids - Array of resource IDs
   * @param {Object} data - Additional action data
   * @returns {Promise}
   */
  async runAction(panel, resource, action, ids, data = {}) {
    const response = await window.axios.post(
      `${this.getResourceUrl(panel, resource)}/actions/${action}`,
      { ids, ...data }
    )
    return response.data
  },

  /**
   * Search related resources within a panel
   * @param {string} panel - Panel key
   * @param {string} resource - Resource name
   * @param {string} query - Search query
   * @param {number} perPage - Results per page
   * @returns {Promise}
   */
  async searchRelated(panel, resource, query, perPage = 10) {
    const response = await window.axios.get(
      `${this.getResourceUrl(panel, resource)}/search`,
      { params: { search: query, perPage } }
    )
    return response.data
  }
}

export default panelService
