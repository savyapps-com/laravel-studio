/**
 * Search Service
 * Handles all API calls for global search functionality
 */

export const searchService = {
  /**
   * Perform global search across all resources
   * @param {string} query - Search query
   * @param {Object} options - Search options
   * @returns {Promise} { query: string, results: Array, grouped: Object, total: number }
   */
  async search(query, options = {}) {
    const params = { q: query }
    if (options.panel) params.panel = options.panel
    if (options.resources) params.resources = options.resources

    const response = await window.axios.get('/api/search', { params })
    return response.data
  },

  /**
   * Search within a specific resource type
   * @param {string} resource - Resource key
   * @param {string} query - Search query
   * @param {number} limit - Maximum results
   * @returns {Promise} { resource: string, query: string, results: Array, total: number }
   */
  async searchResource(resource, query, limit = 10) {
    const response = await window.axios.get(`/api/search/${resource}`, {
      params: { q: query, limit }
    })
    return response.data
  },

  /**
   * Get search suggestions (recent searches, searchable resources)
   * @returns {Promise} { recent: Array, resources: Array }
   */
  async getSuggestions() {
    const response = await window.axios.get('/api/search/suggestions')
    return response.data
  },

  /**
   * Get list of searchable resources
   * @param {string} panel - Optional panel to filter by
   * @returns {Promise} { resources: Array }
   */
  async getSearchableResources(panel = null) {
    const params = panel ? { panel } : {}
    const response = await window.axios.get('/api/search/resources', { params })
    return response.data
  },

  /**
   * Clear recent search history
   * @returns {Promise} { message: string }
   */
  async clearRecentSearches() {
    const response = await window.axios.delete('/api/search/recent')
    return response.data
  },

  /**
   * Get recent searches from local storage
   * @param {number} limit - Maximum number of recent searches
   * @returns {Array}
   */
  getLocalRecentSearches(limit = 5) {
    try {
      const stored = localStorage.getItem('studio_recent_searches')
      if (!stored) return []
      const searches = JSON.parse(stored)
      return searches.slice(0, limit)
    } catch {
      return []
    }
  },

  /**
   * Store a search query locally
   * @param {string} query - Search query to store
   */
  storeLocalSearch(query) {
    try {
      const stored = localStorage.getItem('studio_recent_searches')
      let searches = stored ? JSON.parse(stored) : []

      // Remove duplicate if exists
      searches = searches.filter(s => s !== query)

      // Add to beginning
      searches.unshift(query)

      // Keep only last 10
      searches = searches.slice(0, 10)

      localStorage.setItem('studio_recent_searches', JSON.stringify(searches))
    } catch {
      // Ignore storage errors
    }
  },

  /**
   * Clear local recent searches
   */
  clearLocalRecentSearches() {
    try {
      localStorage.removeItem('studio_recent_searches')
    } catch {
      // Ignore storage errors
    }
  },

  /**
   * Highlight matching text in a string
   * @param {string} text - Text to highlight
   * @param {string} query - Search query to highlight
   * @returns {string} HTML string with highlighted matches
   */
  highlightMatches(text, query) {
    if (!text || !query) return text

    const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi')
    return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>')
  },

  /**
   * Escape special regex characters
   * @param {string} string - String to escape
   * @returns {string}
   */
  escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  },

  /**
   * Get keyboard shortcut display text
   * @returns {string}
   */
  getShortcutDisplay() {
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0
    return isMac ? '⌘K' : 'Ctrl+K'
  },

  /**
   * Check if keyboard shortcut was pressed
   * @param {KeyboardEvent} event - Keyboard event
   * @returns {boolean}
   */
  isShortcutPressed(event) {
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0
    const modifier = isMac ? event.metaKey : event.ctrlKey
    return modifier && event.key.toLowerCase() === 'k'
  }
}

export default searchService
