/**
 * Laravel Studio - Axios Bootstrap
 *
 * Configures axios with authentication token handling and global error/success handling.
 * Import this in your app's bootstrap to set up API communication.
 */
import axios from 'axios'
import { useToast } from '../composables/useToast.js'

/**
 * Configure axios with auth token and interceptors
 * @param {Object} options - Configuration options
 * @param {string} options.tokenKey - localStorage key for auth token (default: 'auth_token')
 * @param {string} options.loginPath - Path to redirect on 401 (default: '/admin')
 * @param {boolean} options.showSuccessToasts - Show success toasts for responses with messages (default: true)
 * @param {boolean} options.showErrorToasts - Show error toasts for failed requests (default: true)
 */
export function configureAxios(options = {}) {
  const {
    tokenKey = 'auth_token',
    loginPath = '/admin',
    showSuccessToasts = true,
    showErrorToasts = true
  } = options

  window.axios = axios

  window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
  window.axios.defaults.headers.common['Accept'] = 'application/json'

  // Request interceptor to add auth token
  window.axios.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem(tokenKey)
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => {
      return Promise.reject(error)
    }
  )

  // Response interceptor for global error handling and success messages
  window.axios.interceptors.response.use(
    (response) => {
      // Show success toast if response contains a message
      if (showSuccessToasts && response.data?.message) {
        const toast = useToast()
        toast.success(response.data.message)
      }
      return response
    },
    (error) => {
      // Handle 401 Unauthorized
      if (error.response?.status === 401) {
        // Clear auth state
        localStorage.removeItem(tokenKey)
        delete window.axios.defaults.headers.common['Authorization']

        // Redirect to login if not already on login page
        if (window.location.pathname !== loginPath) {
          window.location.href = loginPath
        }
        return Promise.reject(error)
      }

      // Show toast notifications for other errors
      if (showErrorToasts) {
        const toast = useToast()
        const message = error.response?.data?.message
          || error.response?.data?.error
          || error.message
          || 'An error occurred'

        const status = error.response?.status

        if (status >= 500) {
          // Server errors
          toast.error(message)
        } else if (status >= 400 && status < 500) {
          // Client errors (validation, not found, etc.)
          toast.warning(message)
        } else if (!error.response) {
          // Network errors
          toast.error('Network error. Please check your connection.')
        }
      }

      return Promise.reject(error)
    }
  )

  return axios
}

export default axios
