/**
 * Card Service
 * Handles all API calls for dashboard cards and widgets
 */

export const cardService = {
  /**
   * Get cards for a resource
   * @param {string} resource - Resource key
   * @returns {Promise} { resource: string, cards: Array, total: number }
   */
  async getResourceCards(resource) {
    const response = await window.axios.get(`/api/cards/${resource}`)
    return response.data
  },

  /**
   * Get dashboard cards for a panel
   * @param {string} panel - Optional panel key
   * @returns {Promise} { panel: string, cards: Array, total: number }
   */
  async getDashboardCards(panel = null) {
    const params = panel ? { panel } : {}
    const response = await window.axios.get('/api/cards/dashboard', { params })
    return response.data
  },

  /**
   * Get a single card's data
   * @param {string} resource - Resource key
   * @param {string} cardKey - Card unique key
   * @returns {Promise} Card data object
   */
  async getCard(resource, cardKey) {
    const response = await window.axios.get(`/api/cards/${resource}/${cardKey}`)
    return response.data
  },

  /**
   * Refresh a card's data (bypasses cache)
   * @param {string} resource - Resource key
   * @param {string} cardKey - Card unique key
   * @returns {Promise} Updated card data
   */
  async refreshCard(resource, cardKey) {
    const response = await window.axios.post(`/api/cards/${resource}/${cardKey}/refresh`)
    return response.data
  },

  /**
   * Get available card types
   * @returns {Promise} { types: Object }
   */
  async getCardTypes() {
    const response = await window.axios.get('/api/cards/types')
    return response.data
  },

  /**
   * Clear card cache for a resource
   * @param {string} resource - Resource key
   * @returns {Promise} { message: string }
   */
  async clearResourceCache(resource) {
    const response = await window.axios.delete(`/api/cards/${resource}/cache`)
    return response.data
  },

  /**
   * Clear all card caches
   * @returns {Promise} { message: string }
   */
  async clearAllCaches() {
    const response = await window.axios.delete('/api/cards/cache')
    return response.data
  },

  /**
   * Format a value based on format type
   * @param {number} value - Value to format
   * @param {string} format - Format type (number, currency, percentage)
   * @param {Object} options - Formatting options
   * @returns {string} Formatted value
   */
  formatValue(value, format = 'number', options = {}) {
    if (value === null || value === undefined) return '-'

    const { currency = 'USD', decimals = 0, locale = 'en-US' } = options

    switch (format) {
      case 'currency':
        return new Intl.NumberFormat(locale, {
          style: 'currency',
          currency: currency,
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        }).format(value)

      case 'percentage':
        return new Intl.NumberFormat(locale, {
          style: 'percent',
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        }).format(value / 100)

      case 'compact':
        return new Intl.NumberFormat(locale, {
          notation: 'compact',
          compactDisplay: 'short'
        }).format(value)

      case 'number':
      default:
        return new Intl.NumberFormat(locale, {
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        }).format(value)
    }
  },

  /**
   * Get trend indicator (up/down/neutral)
   * @param {number} current - Current value
   * @param {number} previous - Previous value
   * @returns {Object} { trend: string, percentage: number }
   */
  getTrend(current, previous) {
    if (previous === null || previous === undefined || previous === 0) {
      if (current > 0) {
        return { trend: 'up', percentage: 100 }
      }
      return { trend: 'neutral', percentage: 0 }
    }

    const change = current - previous
    const percentage = Math.round((change / previous) * 100 * 10) / 10

    return {
      trend: change > 0 ? 'up' : change < 0 ? 'down' : 'neutral',
      percentage: Math.abs(percentage)
    }
  },

  /**
   * Get CSS class for trend color
   * @param {string} trend - Trend direction (up/down/neutral)
   * @param {boolean} inverted - Whether up is bad (e.g., costs)
   * @returns {string} CSS class
   */
  getTrendColorClass(trend, inverted = false) {
    if (trend === 'neutral') return 'text-gray-500'

    if (inverted) {
      return trend === 'up' ? 'text-red-500' : 'text-green-500'
    }

    return trend === 'up' ? 'text-green-500' : 'text-red-500'
  },

  /**
   * Get CSS class for card color
   * @param {string} color - Color name
   * @returns {Object} CSS classes for background and text
   */
  getColorClasses(color) {
    const colorMap = {
      blue: { bg: 'bg-blue-500', text: 'text-blue-500', light: 'bg-blue-100' },
      green: { bg: 'bg-green-500', text: 'text-green-500', light: 'bg-green-100' },
      yellow: { bg: 'bg-yellow-500', text: 'text-yellow-500', light: 'bg-yellow-100' },
      red: { bg: 'bg-red-500', text: 'text-red-500', light: 'bg-red-100' },
      purple: { bg: 'bg-purple-500', text: 'text-purple-500', light: 'bg-purple-100' },
      pink: { bg: 'bg-pink-500', text: 'text-pink-500', light: 'bg-pink-100' },
      indigo: { bg: 'bg-indigo-500', text: 'text-indigo-500', light: 'bg-indigo-100' },
      cyan: { bg: 'bg-cyan-500', text: 'text-cyan-500', light: 'bg-cyan-100' },
      orange: { bg: 'bg-orange-500', text: 'text-orange-500', light: 'bg-orange-100' },
      teal: { bg: 'bg-teal-500', text: 'text-teal-500', light: 'bg-teal-100' },
      gray: { bg: 'bg-gray-500', text: 'text-gray-500', light: 'bg-gray-100' }
    }

    return colorMap[color] || colorMap.blue
  },

  /**
   * Get width CSS class based on card width setting
   * @param {string} width - Width setting (full, 1/2, 1/3, 1/4)
   * @returns {string} CSS class
   */
  getWidthClass(width) {
    const widthMap = {
      'full': 'w-full',
      '1/2': 'w-full md:w-1/2',
      '1/3': 'w-full md:w-1/2 lg:w-1/3',
      '1/4': 'w-full md:w-1/2 lg:w-1/4'
    }

    return widthMap[width] || widthMap['1/4']
  }
}

export default cardService
