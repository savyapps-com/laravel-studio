import { ref, computed, onMounted, onUnmounted } from 'vue'
import cardService from '../services/cardService'

/**
 * Composable for managing dashboard cards
 */
export function useCards(options = {}) {
  const {
    resource = null,
    panel = null,
    autoLoad = true,
    autoRefresh = false,
    refreshInterval = 60000 // 1 minute default
  } = options

  // State
  const cards = ref([])
  const loading = ref(false)
  const error = ref(null)
  const refreshingCards = ref(new Set())

  // Refresh timer
  let refreshTimer = null

  // Computed
  const hasCards = computed(() => cards.value.length > 0)
  const isEmpty = computed(() => !loading.value && !hasCards.value)

  const cardsByWidth = computed(() => {
    const grouped = {
      full: [],
      half: [],
      third: [],
      quarter: []
    }

    cards.value.forEach(card => {
      switch (card.width) {
        case 'full':
          grouped.full.push(card)
          break
        case '1/2':
          grouped.half.push(card)
          break
        case '1/3':
          grouped.third.push(card)
          break
        case '1/4':
        default:
          grouped.quarter.push(card)
          break
      }
    })

    return grouped
  })

  // Methods
  const loadCards = async () => {
    loading.value = true
    error.value = null

    try {
      let response

      if (resource) {
        response = await cardService.getResourceCards(resource)
      } else {
        response = await cardService.getDashboardCards(panel)
      }

      cards.value = response.cards || []
    } catch (err) {
      error.value = err.message || 'Failed to load cards'
      cards.value = []
    } finally {
      loading.value = false
    }
  }

  const refreshCard = async (cardKey) => {
    if (!resource || refreshingCards.value.has(cardKey)) return

    refreshingCards.value.add(cardKey)

    try {
      const updatedCard = await cardService.refreshCard(resource, cardKey)

      // Update the card in our list
      const index = cards.value.findIndex(c => c.key === cardKey)
      if (index !== -1) {
        cards.value[index] = updatedCard
      }

      return updatedCard
    } catch (err) {
      console.error(`Failed to refresh card ${cardKey}:`, err)
      throw err
    } finally {
      refreshingCards.value.delete(cardKey)
    }
  }

  const refreshAllCards = async () => {
    if (!resource) {
      // For dashboard cards, just reload everything
      return loadCards()
    }

    // Refresh each card individually
    const promises = cards.value.map(card => refreshCard(card.key))
    await Promise.allSettled(promises)
  }

  const clearCache = async () => {
    try {
      if (resource) {
        await cardService.clearResourceCache(resource)
      } else {
        await cardService.clearAllCaches()
      }

      // Reload cards after clearing cache
      await loadCards()
    } catch (err) {
      error.value = err.message || 'Failed to clear cache'
      throw err
    }
  }

  const isCardRefreshing = (cardKey) => {
    return refreshingCards.value.has(cardKey)
  }

  const getCardById = (cardKey) => {
    return cards.value.find(c => c.key === cardKey) || null
  }

  const getCardsByType = (type) => {
    return cards.value.filter(c => c.type === type)
  }

  // Auto-refresh setup
  const startAutoRefresh = () => {
    if (refreshTimer) return

    refreshTimer = setInterval(() => {
      refreshAllCards()
    }, refreshInterval)
  }

  const stopAutoRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer)
      refreshTimer = null
    }
  }

  // Lifecycle
  onMounted(() => {
    if (autoLoad) {
      loadCards()
    }

    if (autoRefresh) {
      startAutoRefresh()
    }
  })

  onUnmounted(() => {
    stopAutoRefresh()
  })

  return {
    // State
    cards,
    loading,
    error,
    refreshingCards,

    // Computed
    hasCards,
    isEmpty,
    cardsByWidth,

    // Methods
    loadCards,
    refreshCard,
    refreshAllCards,
    clearCache,
    isCardRefreshing,
    getCardById,
    getCardsByType,
    startAutoRefresh,
    stopAutoRefresh,

    // Utilities from service
    formatValue: cardService.formatValue,
    getTrend: cardService.getTrend,
    getTrendColorClass: cardService.getTrendColorClass,
    getColorClasses: cardService.getColorClasses,
    getWidthClass: cardService.getWidthClass
  }
}

export default useCards
