/**
 * Laravel Studio - Core Components
 *
 * Essential components for basic admin functionality.
 * Import this for minimal bundle size.
 *
 * Usage:
 * import { ResourceManager, ResourceTable } from 'laravel-studio/core'
 */

// Core Resource Components
export { default as ResourceManager } from '../components/resource/ResourceManager.vue'
export { default as ResourceTable } from '../components/resource/ResourceTable.vue'
export { default as ResourceForm } from '../components/resource/ResourceForm.vue'
export { default as FieldRenderer } from '../components/resource/FieldRenderer.vue'
export { default as FilterBar } from '../components/resource/FilterBar.vue'
export { default as ActionButtons } from '../components/resource/ActionButtons.vue'
export { default as QuickCreateModal } from '../components/resource/QuickCreateModal.vue'

// Layout Components
export { default as PanelLayout } from '../components/layout/PanelLayout.vue'
export { default as PanelSwitcher } from '../components/layout/PanelSwitcher.vue'

// Permission Components
export { default as PermissionGuard } from '../components/permissions/PermissionGuard.vue'

// Essential Form Components
export { default as FormInput } from '../components/form/FormInput.vue'
export { default as SelectInput } from '../components/form/SelectInput.vue'
export { default as DateInput } from '../components/form/DateInput.vue'
export { default as CheckboxInput } from '../components/form/CheckboxInput.vue'
export { default as TextareaInput } from '../components/form/TextareaInput.vue'
export { default as PasswordInput } from '../components/form/PasswordInput.vue'
export { default as FormGroup } from '../components/form/FormGroup.vue'
export { default as FormLabel } from '../components/form/FormLabel.vue'
export { default as FormError } from '../components/form/FormError.vue'
export { default as FormActions } from '../components/form/FormActions.vue'

// Common Components
export { default as Toast } from '../components/common/Toast.vue'
export { default as ToastContainer } from '../components/common/ToastContainer.vue'
export { default as ConfirmDialog } from '../components/common/ConfirmDialog.vue'
export { default as ConfirmDialogContainer } from '../components/common/ConfirmDialogContainer.vue'
export { default as Icon } from '../components/common/Icon.vue'
export { default as ToggleSwitch } from '../components/common/ToggleSwitch.vue'

// Essential Services
export { resourceService } from '../services/resourceService.js'
export { panelService } from '../services/panelService.js'
export { authService } from '../services/authService.js'
export { permissionService } from '../services/permissionService.js'

// Stores
export { useAuthStore } from '../stores/auth.js'
export { usePanelStore } from '../stores/panel.js'
export { useDialogStore } from '../stores/dialog.js'
export { useToastStore } from '../stores/toast.js'

// Essential Composables
export { useToast } from '../composables/useToast.js'
export { useDialog } from '../composables/useDialog.js'
export { usePermissions } from '../composables/usePermissions.js'

// Router Utilities
export {
    generatePanelRoutes,
    loadDynamicPanelRoutes,
    createPanelGuard,
    generateStaticPanelRoutes,
    getPanelFromRoute,
    getResourceFromRoute
} from '../router/panelRoutes.js'

// Directives
export { default as tooltipDirective } from '../directives/tooltip.js'
