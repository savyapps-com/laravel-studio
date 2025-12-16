/**
 * Laravel Error Mapper Utilities
 * Handles Laravel validation errors and maps them to VeeValidate format
 */

/**
 * Handle Laravel validation errors and set them on VeeValidate form
 * @param {Error} error - The error from axios/API call
 * @param {Function} setErrors - VeeValidate setErrors function
 * @returns {boolean} - True if validation errors were handled, false otherwise
 */
export function handleLaravelValidationErrors(error, setErrors) {
  // Check if this is a Laravel validation error (422 status)
  if (error.response?.status === 422 && error.response?.data?.errors) {
    const laravelErrors = error.response.data.errors
    const veeValidateErrors = {}

    // Convert Laravel's error format to VeeValidate format
    // Laravel: { field: ['Error message 1', 'Error message 2'] }
    // VeeValidate: { field: 'Error message 1' }
    for (const [field, messages] of Object.entries(laravelErrors)) {
      // Take the first error message for each field
      veeValidateErrors[field] = Array.isArray(messages) ? messages[0] : messages
    }

    setErrors(veeValidateErrors)
    return true
  }

  return false
}

/**
 * Get a user-friendly error message from Laravel error response
 * @param {Error} error - The error from axios/API call
 * @returns {string} - User-friendly error message
 */
export function getLaravelErrorMessage(error) {
  // Check for Laravel's message field
  if (error.response?.data?.message) {
    return error.response.data.message
  }

  // Check for general error message
  if (error.response?.data?.error) {
    return error.response.data.error
  }

  // Handle specific HTTP status codes
  switch (error.response?.status) {
    case 401:
      return 'Invalid credentials. Please check your email and password.'
    case 403:
      return 'You do not have permission to perform this action.'
    case 404:
      return 'The requested resource was not found.'
    case 419:
      return 'Your session has expired. Please refresh the page and try again.'
    case 422:
      return 'Please check the form for errors.'
    case 429:
      return 'Too many attempts. Please try again later.'
    case 500:
      return 'An unexpected error occurred. Please try again later.'
    case 503:
      return 'Service temporarily unavailable. Please try again later.'
    default:
      break
  }

  // Network error
  if (error.code === 'ERR_NETWORK' || !error.response) {
    return 'Unable to connect to the server. Please check your internet connection.'
  }

  // Fallback to generic message
  return error.message || 'An unexpected error occurred. Please try again.'
}

/**
 * Extract all validation error messages as a flat array
 * @param {Error} error - The error from axios/API call
 * @returns {string[]} - Array of error messages
 */
export function getValidationErrorMessages(error) {
  if (error.response?.status === 422 && error.response?.data?.errors) {
    const errors = error.response.data.errors
    return Object.values(errors).flat()
  }
  return []
}

/**
 * Check if the error is a validation error
 * @param {Error} error - The error from axios/API call
 * @returns {boolean} - True if it's a validation error
 */
export function isValidationError(error) {
  return error.response?.status === 422 && error.response?.data?.errors
}

/**
 * Check if the error is an authentication error
 * @param {Error} error - The error from axios/API call
 * @returns {boolean} - True if it's an auth error
 */
export function isAuthError(error) {
  return error.response?.status === 401
}

/**
 * Check if the error is a forbidden error
 * @param {Error} error - The error from axios/API call
 * @returns {boolean} - True if it's a forbidden error
 */
export function isForbiddenError(error) {
  return error.response?.status === 403
}
