import { ref, computed } from 'vue'

/**
 * Password visibility toggle composable
 *
 * Provides reactive state for toggling password visibility
 * between 'password' and 'text' input types.
 */
export function usePasswordToggle() {
    const showPassword = ref(false)

    const togglePassword = () => {
        showPassword.value = !showPassword.value
    }

    const inputType = computed(() => showPassword.value ? 'text' : 'password')

    return {
        showPassword,
        togglePassword,
        inputType
    }
}
