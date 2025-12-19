/**
 * Composables for Authentication Forms
 *
 * These composables provide form state, validation, and submission logic
 * for common auth flows (login, register, forgot password, reset password).
 *
 * They are designed to work with VeeValidate but can be adapted for other form libraries.
 */

import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

/**
 * Base composable for auth forms
 * Provides common state management and error handling
 *
 * @param {Object} options - Configuration options
 * @param {Function} options.submitFn - The async function to call on submit
 * @param {Function} options.onSuccess - Callback on successful submission
 * @param {Function} options.onError - Callback on error
 * @param {Function} options.errorMapper - Function to map error responses to form errors
 */
export function useAuthForm(options = {}) {
  const {
    submitFn,
    onSuccess,
    onError,
    errorMapper = defaultErrorMapper
  } = options

  const isSubmitting = ref(false)
  const successMessage = ref('')
  const errorMessage = ref('')
  const fieldErrors = ref({})

  const clearMessages = () => {
    successMessage.value = ''
    errorMessage.value = ''
    fieldErrors.value = {}
  }

  const handleSubmit = async (values) => {
    if (!submitFn) {
      console.warn('useAuthForm: No submitFn provided')
      return
    }

    isSubmitting.value = true
    clearMessages()

    try {
      const result = await submitFn(values)

      if (onSuccess) {
        await onSuccess(result)
      }

      return result
    } catch (error) {
      const mapped = errorMapper(error)

      if (mapped.fieldErrors && Object.keys(mapped.fieldErrors).length > 0) {
        fieldErrors.value = mapped.fieldErrors
      }

      if (mapped.message) {
        errorMessage.value = mapped.message
      }

      if (onError) {
        onError(error, mapped)
      }

      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    isSubmitting,
    successMessage,
    errorMessage,
    fieldErrors,
    clearMessages,
    handleSubmit,
  }
}

/**
 * Login form composable
 *
 * @param {Object} options - Configuration options
 * @param {Object} options.authStore - The auth store instance (Pinia)
 * @param {string} options.defaultRedirect - Default route name to redirect after login
 * @param {Object} options.panelDashboards - Map of panel names to dashboard route configs
 */
export function useLoginForm(options = {}) {
  const {
    authStore,
    defaultRedirect = 'admin.dashboard',
    panelDashboards = {},
    redirectDelay = 1000,
  } = options

  const router = useRouter()
  const route = useRoute()

  const form = useAuthForm({
    submitFn: async (values) => {
      if (!authStore) {
        throw new Error('authStore is required for useLoginForm')
      }

      const panel = route.params.panel
      const loginData = panel ? { ...values, panel } : values

      await authStore.login(loginData)
      return { panel }
    },
    onSuccess: async ({ panel }) => {
      form.successMessage.value = 'Login successful! Redirecting...'

      // Determine redirect destination
      let redirectTo = getRedirectDestination(
        route,
        router,
        authStore,
        panel,
        defaultRedirect,
        panelDashboards
      )

      // Redirect after delay
      setTimeout(() => {
        router.push(redirectTo)
      }, redirectDelay)
    },
  })

  return form
}

/**
 * Registration form composable
 *
 * @param {Object} options - Configuration options
 * @param {Object} options.authStore - The auth store instance (Pinia)
 * @param {string} options.successRedirect - Route to redirect after successful registration
 */
export function useRegisterForm(options = {}) {
  const {
    authStore,
    successRedirect = null,
    redirectDelay = 1000,
  } = options

  const router = useRouter()
  const route = useRoute()

  const form = useAuthForm({
    submitFn: async (values) => {
      if (!authStore) {
        throw new Error('authStore is required for useRegisterForm')
      }

      const panel = route.params.panel
      const registerData = panel ? { ...values, panel } : values

      await authStore.register(registerData)
      return { panel }
    },
    onSuccess: async ({ panel }) => {
      form.successMessage.value = 'Registration successful!'

      if (successRedirect) {
        setTimeout(() => {
          router.push(successRedirect)
        }, redirectDelay)
      }
    },
  })

  return form
}

/**
 * Forgot password form composable
 *
 * @param {Object} options - Configuration options
 * @param {Function} options.sendResetLink - Function to call to send reset email
 */
export function useForgotPasswordForm(options = {}) {
  const { sendResetLink } = options

  const form = useAuthForm({
    submitFn: async (values) => {
      if (!sendResetLink) {
        throw new Error('sendResetLink function is required for useForgotPasswordForm')
      }

      await sendResetLink(values.email)
    },
    onSuccess: () => {
      form.successMessage.value = 'Password reset link sent! Please check your email.'
    },
  })

  return form
}

/**
 * Reset password form composable
 *
 * @param {Object} options - Configuration options
 * @param {Function} options.resetPassword - Function to call to reset password
 * @param {string} options.successRedirect - Route to redirect after successful reset
 */
export function useResetPasswordForm(options = {}) {
  const {
    resetPassword,
    successRedirect = 'panel.login',
    redirectDelay = 2000,
  } = options

  const router = useRouter()
  const route = useRoute()

  const form = useAuthForm({
    submitFn: async (values) => {
      if (!resetPassword) {
        throw new Error('resetPassword function is required for useResetPasswordForm')
      }

      const token = route.params.token || route.query.token
      await resetPassword({ ...values, token })
    },
    onSuccess: () => {
      form.successMessage.value = 'Password reset successfully! Redirecting to login...'

      if (successRedirect) {
        setTimeout(() => {
          const panel = route.params.panel || 'admin'
          router.push({ name: successRedirect, params: { panel } })
        }, redirectDelay)
      }
    },
  })

  return form
}

/**
 * Change password form composable (for authenticated users)
 *
 * @param {Object} options - Configuration options
 * @param {Function} options.changePassword - Function to call to change password
 */
export function useChangePasswordForm(options = {}) {
  const { changePassword } = options

  const form = useAuthForm({
    submitFn: async (values) => {
      if (!changePassword) {
        throw new Error('changePassword function is required for useChangePasswordForm')
      }

      await changePassword(values)
    },
    onSuccess: () => {
      form.successMessage.value = 'Password changed successfully!'
    },
  })

  return form
}

// Helper functions

function getRedirectDestination(route, router, authStore, panel, defaultRedirect, panelDashboards) {
  // Check for redirect query param
  if (route.query.redirect) {
    const redirectPath = route.query.redirect
    const resolvedRoute = router.resolve(redirectPath)

    // Validate redirect path
    if (
      resolvedRoute.matched.length > 0 &&
      !resolvedRoute.name?.toString().includes('notFound') &&
      !resolvedRoute.name?.toString().includes('NotFound')
    ) {
      return redirectPath
    }
  }

  // Use panel-specific dashboard if configured
  if (panel && panelDashboards[panel]) {
    return panelDashboards[panel]
  }

  // Check if user can access admin panel
  if (authStore.user?.can_access_admin_panel) {
    return { name: 'admin.dashboard' }
  }

  // Default fallback
  if (panel) {
    return { name: 'panel.dashboard', params: { panel } }
  }

  return { name: defaultRedirect }
}

function defaultErrorMapper(error) {
  const result = {
    message: '',
    fieldErrors: {},
  }

  // Handle Laravel validation errors
  if (error.response?.status === 422) {
    const data = error.response.data

    if (data.errors) {
      result.fieldErrors = Object.fromEntries(
        Object.entries(data.errors).map(([field, messages]) => [
          field,
          Array.isArray(messages) ? messages[0] : messages
        ])
      )
    }

    if (data.message) {
      result.message = data.message
    }
  } else if (error.response?.data?.message) {
    result.message = error.response.data.message
  } else if (error.message) {
    result.message = error.message
  } else {
    result.message = 'An unexpected error occurred. Please try again.'
  }

  return result
}
