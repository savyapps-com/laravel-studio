<template>
  <AuthPage
    title="Welcome Back"
    description="Sign in to your account to continue"
    :logo-title="panelLabel"
    :help-text="helpText"
  >
    <template #form>
      <form @submit.prevent="onSubmit" class="space-y-4">
        <!-- Email Field -->
        <FormGroup>
          <FormLabel for-id="email" :required="true">Email Address</FormLabel>
          <FormInput
            id="email"
            name="email"
            type="email"
            placeholder="Enter your email address"
          />
        </FormGroup>

        <!-- Password Field -->
        <FormGroup>
          <FormLabel for-id="password" :required="true">Password</FormLabel>
          <PasswordInput
            id="password"
            name="password"
            placeholder="Enter your password"
          />
        </FormGroup>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
          <CheckboxInput
            id="remember"
            name="remember"
            label="Remember me"
          />
        </div>

        <!-- Success Message -->
        <FormSuccess :message="successMessage" />

        <!-- General Error Message -->
        <div v-if="errorMessage" class="auth-error bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
          <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ errorMessage }}</p>
          </div>
        </div>
      </form>
    </template>

    <template #actions>
      <button
        @click="onSubmit"
        :disabled="isSubmitting"
        class="auth-button-primary"
      >
        <span v-if="isSubmitting" class="flex items-center justify-center">
          <Icon name="loading" :size="20" class="animate-spin mr-2" />
          Signing In...
        </span>
        <span v-else>Sign In</span>
      </button>
    </template>

    <template #links>
      <div class="space-y-2">
        <router-link :to="{ name: 'panel.forgot-password', params: { panel: currentPanel } }" class="auth-link block">
          Forgot your password?
        </router-link>
      </div>
    </template>

    <template #footer>
      <p v-if="allowRegistration" class="text-sm text-gray-500 dark:text-gray-400">
        Don't have an account?
        <router-link :to="{ name: 'panel.register', params: { panel: currentPanel } }" class="auth-link">
          Sign up here
        </router-link>
      </p>
    </template>
  </AuthPage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import AuthPage from '../../components/auth/AuthPage.vue'
import Icon from '../../components/common/Icon.vue'
import FormGroup from '../../components/form/FormGroup.vue'
import FormLabel from '../../components/form/FormLabel.vue'
import FormInput from '../../components/form/FormInput.vue'
import PasswordInput from '../../components/form/PasswordInput.vue'
import CheckboxInput from '../../components/form/CheckboxInput.vue'
import FormSuccess from '../../components/form/FormSuccess.vue'
import { authService } from '../../services/authService.js'
import { useAuthStore } from '../../stores/auth.js'
import { loginSchema } from '../../utils/validationSchemas.js'
import { handleLaravelValidationErrors, getLaravelErrorMessage } from '../../utils/laravelErrorMapper.js'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const helpText = 'Having trouble signing in? Make sure you\'re using the correct email and password.'

// Get current panel from route params
const currentPanel = computed(() => route.params.panel || 'admin')

// Panel settings
const panelLabel = ref('')
const allowRegistration = ref(false)
const panelLoading = ref(true)

// Form state
const isSubmitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Prefill credentials in local environment for development convenience
const isLocalEnv = import.meta.env.VITE_APP_ENV === 'local'

const { handleSubmit, setErrors, setFieldValue } = useForm({
  validationSchema: loginSchema,
  initialValues: {
    email: isLocalEnv ? 'user@app.com' : '',
    password: isLocalEnv ? 'password' : '',
    remember: false
  },
  validateOnMount: false
})

// Fetch panel info to check registration settings
onMounted(async () => {
  try {
    const panelInfo = await authService.getPanelInfo(currentPanel.value)
    panelLabel.value = panelInfo.label || ''
    allowRegistration.value = panelInfo.allow_registration || false
  } catch (error) {
    // If panel info fetch fails, use defaults
    panelLabel.value = ''
    allowRegistration.value = false
  } finally {
    panelLoading.value = false
  }
})

const onSubmit = handleSubmit(async (values) => {
  isSubmitting.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    // Include the panel from the route in the login request
    const panel = route.params.panel
    const loginData = panel ? { ...values, panel } : values

    await authStore.login(loginData)
    successMessage.value = 'Login successful! Redirecting...'

    // Determine redirect destination based on the panel they logged into
    let redirectTo
    let defaultDashboard

    // If logging in from a specific panel route, redirect to that panel's dashboard
    if (panel === 'admin') {
      defaultDashboard = { name: 'admin.dashboard' }
    } else if (panel) {
      defaultDashboard = { name: 'panel.dashboard', params: { panel } }
    } else {
      // Fallback to admin dashboard if user has access, otherwise use panel dashboard
      defaultDashboard = authStore.user?.can_access_admin_panel
        ? { name: 'admin.dashboard' }
        : { name: 'panel.dashboard', params: { panel: 'user' } }
    }

    if (route.query.redirect) {
      // Validate that the redirect path exists as a route
      const redirectPath = route.query.redirect
      const resolvedRoute = router.resolve(redirectPath)

      // Check if the route exists and is not a 404 catch-all
      if (resolvedRoute.matched.length > 0 &&
          !resolvedRoute.name?.toString().includes('notFound') &&
          !resolvedRoute.name?.toString().includes('NotFound')) {
        redirectTo = redirectPath
      } else {
        // Invalid redirect path, use default dashboard
        redirectTo = defaultDashboard
      }
    } else {
      redirectTo = defaultDashboard
    }

    // Redirect to intended route or dashboard
    setTimeout(() => {
      router.push(redirectTo)
    }, 1000)
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
</script>
