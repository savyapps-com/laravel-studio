import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

/**
 * Composable for managing sidebar state (mobile/desktop toggle, collapsed state)
 *
 * @param {Object} options - Configuration options
 * @param {string} options.storageKey - LocalStorage key for persisting sidebar state
 * @param {number} options.mobileBreakpoint - Breakpoint width for mobile detection (default: 1024)
 * @returns {Object} Sidebar state and methods
 */
export function useSidebar(options = {}) {
  const {
    storageKey = 'admin-sidebar-collapsed',
    mobileBreakpoint = 1024
  } = options

  // Reactive state
  const isMobileSidebarOpen = ref(false)
  const isDesktopSidebarCollapsed = ref(false)
  const isMobile = ref(false)

  // Load saved sidebar state from localStorage
  const loadSidebarState = () => {
    try {
      const saved = localStorage.getItem(storageKey)
      if (saved !== null) {
        isDesktopSidebarCollapsed.value = JSON.parse(saved)
      }
    } catch (error) {
      console.warn('Failed to load sidebar state from localStorage:', error)
    }
  }

  // Save sidebar state to localStorage
  const saveSidebarState = () => {
    try {
      localStorage.setItem(storageKey, JSON.stringify(isDesktopSidebarCollapsed.value))
    } catch (error) {
      console.warn('Failed to save sidebar state to localStorage:', error)
    }
  }

  // Watch for desktop sidebar state changes and save to localStorage
  watch(isDesktopSidebarCollapsed, saveSidebarState)

  // Check if device is mobile
  const checkMobile = () => {
    isMobile.value = window.innerWidth < mobileBreakpoint
    // Auto-hide sidebar on mobile
    if (isMobile.value) {
      isMobileSidebarOpen.value = false
    }
  }

  // Methods
  const toggleDesktopSidebar = () => {
    isDesktopSidebarCollapsed.value = !isDesktopSidebarCollapsed.value
  }

  const toggleMobileSidebar = () => {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value
    if (isMobileSidebarOpen.value) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  }

  const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false
    document.body.style.overflow = ''
  }

  const closeMobileSidebarOnNavigation = () => {
    // Close sidebar when navigating on mobile
    if (isMobile.value) {
      closeMobileSidebar()
    }
  }

  // Handle resize
  const handleResize = () => {
    checkMobile()
    if (isMobile.value && isMobileSidebarOpen.value) {
      // Keep mobile sidebar behavior as is
    } else if (!isMobile.value && isMobileSidebarOpen.value) {
      // Close mobile sidebar when switching to desktop
      closeMobileSidebar()
    }
  }

  // Computed properties for main content margin
  const mainContentClasses = computed(() => {
    return !isMobile.value && !isDesktopSidebarCollapsed.value ? 'lg:ml-64' : ''
  })

  // Lifecycle hooks
  onMounted(() => {
    loadSidebarState()
    checkMobile()
    window.addEventListener('resize', handleResize)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    document.body.style.overflow = ''
  })

  return {
    // State
    isMobileSidebarOpen,
    isDesktopSidebarCollapsed,
    isMobile,

    // Computed
    mainContentClasses,

    // Methods
    toggleDesktopSidebar,
    toggleMobileSidebar,
    closeMobileSidebar,
    closeMobileSidebarOnNavigation,
  }
}

/**
 * Composable for escape key handling
 * @param {Function} callback - Function to call when escape is pressed
 */
export function useEscapeKey(callback) {
  const handleEscape = (event) => {
    if (event.key === 'Escape') {
      callback()
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', handleEscape)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
  })
}

/**
 * Composable for detecting clicks outside an element
 * @param {Ref} elementRef - Vue ref to the element to watch
 * @param {Function} callback - Function to call when click is detected outside
 */
export function useClickOutside(elementRef, callback) {
  const handleClickOutside = (event) => {
    if (elementRef.value && !elementRef.value.contains(event.target)) {
      callback()
    }
  }

  onMounted(() => {
    document.addEventListener('click', handleClickOutside)
  })

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
  })

  return {
    handleClickOutside,
  }
}
