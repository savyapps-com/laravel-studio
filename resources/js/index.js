/**
 * Laravel Studio - Vue 3 Components
 *
 * @package laravel-studio
 * @author SavyApps
 */

// Core Resource Components
export { default as ResourceManager } from './components/resource/ResourceManager.vue'
export { default as ResourceTable } from './components/resource/ResourceTable.vue'
export { default as ResourceForm } from './components/resource/ResourceForm.vue'
export { default as FieldRenderer } from './components/resource/FieldRenderer.vue'
export { default as FilterBar } from './components/resource/FilterBar.vue'
export { default as ActionButtons } from './components/resource/ActionButtons.vue'
export { default as QuickCreateModal } from './components/resource/QuickCreateModal.vue'

// Common Components
export { default as Toast } from './components/common/Toast.vue'
export { default as ToastContainer } from './components/common/ToastContainer.vue'
export { default as ConfirmDialog } from './components/common/ConfirmDialog.vue'
export { default as ConfirmDialogContainer } from './components/common/ConfirmDialogContainer.vue'
export { default as Icon } from './components/common/Icon.vue'

// Form Components
export { default as FormInput } from './components/form/FormInput.vue'
export { default as SelectInput } from './components/form/SelectInput.vue'
export { default as VirtualSelectInput } from './components/form/VirtualSelectInput.vue'
export { default as ServerSelectInput } from './components/form/ServerSelectInput.vue'
export { default as ResourceSelectInput } from './components/form/ResourceSelectInput.vue'
export { default as DateInput } from './components/form/DateInput.vue'
export { default as CheckboxInput } from './components/form/CheckboxInput.vue'
export { default as TextareaInput } from './components/form/TextareaInput.vue'
export { default as PasswordInput } from './components/form/PasswordInput.vue'
export { default as MediaUpload } from './components/form/MediaUpload.vue'
export { default as FileInput } from './components/form/FileInput.vue'
export { default as RadioGroup } from './components/form/RadioGroup.vue'
export { default as JsonEditor } from './components/form/JsonEditor.vue'
export { default as FormGroup } from './components/form/FormGroup.vue'
export { default as FormLabel } from './components/form/FormLabel.vue'
export { default as FormError } from './components/form/FormError.vue'
export { default as FormSuccess } from './components/form/FormSuccess.vue'
export { default as FormHelpText } from './components/form/FormHelpText.vue'
export { default as FormSection } from './components/form/FormSection.vue'
export { default as FormActions } from './components/form/FormActions.vue'

// Services
export { resourceService } from './services/resourceService.js'

// Composables
export { useToast } from './composables/useToast.js'
export { useDialog } from './composables/useDialog.js'
export { useModal } from './composables/useModal.js'

// Directives
export { default as tooltipDirective } from './directives/tooltip.js'

/**
 * Vue Plugin Installation
 *
 * Usage:
 * import LaravelStudio from 'laravel-studio'
 * app.use(LaravelStudio)
 */
export default {
    install(app, options = {}) {
        // Register global core components
        app.component('ResourceManager', () => import('./components/resource/ResourceManager.vue'))
        app.component('ResourceTable', () => import('./components/resource/ResourceTable.vue'))
        app.component('ResourceForm', () => import('./components/resource/ResourceForm.vue'))

        // Register tooltip directive
        app.directive('tooltip', tooltipDirective)

        // Optionally provide global config
        if (options.apiPrefix) {
            app.provide('studio-api-prefix', options.apiPrefix)
        }
    }
}
