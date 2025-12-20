import { useRouter } from 'vue-router'
import { useContextRoutes } from './useContextRoutes.js'

/**
 * Composable for handling errors and navigating to error pages
 *
 * @param {Object} options - Configuration options
 * @param {Object} options.errorRoutes - Custom error route mapping
 * @param {Function} options.isMaintenanceModeFn - Function to check maintenance mode
 */
export function useErrorHandler(options = {}) {
  const router = useRouter()
  const { errorRoutes: contextErrorRoutes } = useContextRoutes(options)

  const {
    errorRoutes: customErrorRoutes = null,
    isMaintenanceModeFn = () => false
  } = options

  // Use custom routes if provided, otherwise use context-aware routes
  const getErrorRoute = (errorType) => {
    if (customErrorRoutes && customErrorRoutes[errorType]) {
      return customErrorRoutes[errorType]
    }
    return contextErrorRoutes.value[errorType] || contextErrorRoutes.value.notFound
  }

  const handleError = (error, context = {}) => {
    console.error('Error occurred:', error, context)

    // Determine error type and navigate to appropriate page
    if (error.response) {
      const status = error.response.status

      switch (status) {
        case 401:
          router.push({ name: getErrorRoute('unauthorized') })
          break
        case 403:
          router.push({ name: getErrorRoute('forbidden') })
          break
        case 404:
          router.push({ name: getErrorRoute('notFound') })
          break
        case 500:
        case 502:
        case 503:
        case 504:
          router.push({ name: getErrorRoute('serverError') })
          break
        default:
          // For other HTTP errors, show server error page
          router.push({ name: getErrorRoute('serverError') })
      }
    } else if (error.code === 'NETWORK_ERROR' || !error.response) {
      // Network errors (no response from server)
      router.push({ name: getErrorRoute('networkError') })
    } else {
      // Generic error fallback
      router.push({ name: getErrorRoute('serverError') })
    }
  }

  const navigateToError = (errorType) => {
    const statusToType = {
      '401': 'unauthorized',
      '403': 'forbidden',
      '404': 'notFound',
      '500': 'serverError',
      'network': 'networkError',
      'maintenance': 'maintenance',
    }

    const type = statusToType[errorType] || errorType
    const routeName = getErrorRoute(type)

    if (routeName) {
      router.push({ name: routeName })
    } else {
      console.warn(`Unknown error type: ${errorType}`)
      router.push({ name: getErrorRoute('notFound') })
    }
  }

  const isMaintenanceMode = () => {
    return isMaintenanceModeFn()
  }

  const handleMaintenanceCheck = () => {
    if (isMaintenanceMode()) {
      router.push({ name: getErrorRoute('maintenance') })
      return true
    }
    return false
  }

  return {
    handleError,
    navigateToError,
    isMaintenanceMode,
    handleMaintenanceCheck,
  }
}
