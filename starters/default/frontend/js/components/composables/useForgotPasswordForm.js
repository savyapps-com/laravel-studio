/**
 * Composable for Forgot Password Form with VeeValidate
 * Provides form state, validation, and submission logic
 */

import { ref } from 'vue'
import { useForm } from 'vee-validate'
import { forgotPasswordSchema } from '@/utils/validationSchemas'
import { authService } from '@core/services/authService'
import { handleLaravelValidationErrors, getLaravelErrorMessage } from 'laravel-studio'

/**
 * @param {string} panel - The panel key for panel-aware reset URLs (default: 'admin')
 */
export function useForgotPasswordForm(panel = 'admin') {
  const isSubmitting = ref(false)
  const successMessage = ref('')
  const errorMessage = ref('')

  const { handleSubmit, errors, values, setErrors, setFieldValue, resetForm } = useForm({
    validationSchema: forgotPasswordSchema,
    initialValues: {
      email: ''
    },
    validateOnMount: false
  })

  const onSubmit = handleSubmit(async (values) => {
    isSubmitting.value = true
    successMessage.value = ''
    errorMessage.value = ''

    try {
      const response = await authService.forgotPassword(values.email, panel)
      successMessage.value = response.message || 'Password reset link sent to your email.'
      resetForm()
    } catch (error) {
      // Handle validation errors
      if (!handleLaravelValidationErrors(error, setErrors)) {
        // Handle other errors
        errorMessage.value = getLaravelErrorMessage(error)
      }
    } finally {
      isSubmitting.value = false
    }
  })

  return {
    onSubmit,
    errors,
    values,
    isSubmitting,
    successMessage,
    errorMessage,
    setFieldValue,
    resetForm
  }
}