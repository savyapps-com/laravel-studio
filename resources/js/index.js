/**
 * Laravel Studio - Vue 3 Components
 *
 * @package laravel-studio
 * @author SavyApps
 *
 * For smaller bundle sizes, consider importing from specific entry points:
 * - 'laravel-studio/core' - Essential components only (~40% smaller)
 * - 'laravel-studio/activity' - Activity logging components
 * - 'laravel-studio/cards' - Dashboard card components
 * - 'laravel-studio/search' - Global search components
 */

import { defineAsyncComponent } from 'vue'

// Core Resource Components
export { default as ResourceManager } from './components/resource/ResourceManager.vue'
export { default as ResourceTable } from './components/resource/ResourceTable.vue'
export { default as ResourceForm } from './components/resource/ResourceForm.vue'
export { default as FieldRenderer } from './components/resource/FieldRenderer.vue'
export { default as FilterBar } from './components/resource/FilterBar.vue'
export { default as ActionButtons } from './components/resource/ActionButtons.vue'
export { default as QuickCreateModal } from './components/resource/QuickCreateModal.vue'

// Layout Components (Panel)
export { default as PanelLayout } from './components/layout/PanelLayout.vue'
export { default as PanelSwitcher } from './components/layout/PanelSwitcher.vue'

// Layout Components (Admin)
export { default as Sidebar } from './components/layouts/Sidebar.vue'
export { default as MiniSidebar } from './components/layouts/MiniSidebar.vue'
export { default as CompactSidebar } from './components/layouts/CompactSidebar.vue'
export { default as Navbar } from './components/layouts/Navbar.vue'
export { default as HorizontalNav } from './components/layouts/HorizontalNav.vue'
export { default as NavItem } from './components/layouts/NavItem.vue'
export { default as UserDropdown } from './components/layouts/UserDropdown.vue'
export { default as DarkModeToggle } from './components/layouts/DarkModeToggle.vue'

// Auth Components
export { default as AuthPage } from './components/auth/AuthPage.vue'

// Error Components
export { default as ErrorPage } from './components/errors/ErrorPage.vue'

// Profile Components
export { default as ProfileUpdateForm } from './components/profile/ProfileUpdateForm.vue'
export { default as PasswordChangeForm } from './components/profile/PasswordChangeForm.vue'
export { default as SessionManagement } from './components/profile/SessionManagement.vue'

// Table Components
export { default as DataTable } from './components/tables/DataTable.vue'

// Permission Components
export { default as PermissionGuard } from './components/permissions/PermissionGuard.vue'
// Heavy component - async loaded
export const RolePermissionMatrix = defineAsyncComponent(() =>
    import('./components/permissions/RolePermissionMatrix.vue')
)

// Activity Components
export { default as ActivityTimeline } from './components/activity/ActivityTimeline.vue'
export { default as ActivityItem } from './components/activity/ActivityItem.vue'
export { default as ActivityDiff } from './components/activity/ActivityDiff.vue'
export { default as ActivityDetailsModal } from './components/activity/ActivityDetailsModal.vue'

// Search Components
export { default as SearchPalette } from './components/search/SearchPalette.vue'
export { default as SearchResultItem } from './components/search/SearchResultItem.vue'

// Card/Widget Components
export { default as CardGrid } from './components/cards/CardGrid.vue'
export { default as ValueCard } from './components/cards/ValueCard.vue'
export { default as TrendCard } from './components/cards/TrendCard.vue'
export { default as PartitionCard } from './components/cards/PartitionCard.vue'
export { default as TableCard } from './components/cards/TableCard.vue'
// Heavy component - async loaded (includes chart library)
export const ChartCard = defineAsyncComponent(() =>
    import('./components/cards/ChartCard.vue')
)

// Common Components
export { default as Toast } from './components/common/Toast.vue'
export { default as ToastContainer } from './components/common/ToastContainer.vue'
export { default as ConfirmDialog } from './components/common/ConfirmDialog.vue'
export { default as ConfirmDialogContainer } from './components/common/ConfirmDialogContainer.vue'
export { default as Icon } from './components/common/Icon.vue'
export { default as ToggleSwitch } from './components/common/ToggleSwitch.vue'
export { default as VirtualScroll } from './components/common/VirtualScroll.vue'
// Heavy component - async loaded (includes cropper library)
export const ImageEditor = defineAsyncComponent(() =>
    import('./components/common/ImageEditor.vue')
)
export { default as ImageLightbox } from './components/common/ImageLightbox.vue'
export { default as ImageWithBlurPlaceholder } from './components/common/ImageWithBlurPlaceholder.vue'
export { default as BaseModal } from './components/common/BaseModal.vue'
export { default as ModalDialog } from './components/common/ModalDialog.vue'
export { default as DropdownMenu } from './components/common/DropdownMenu.vue'
export { default as ImpersonationBanner } from './components/common/ImpersonationBanner.vue'
export { default as UploadProgressBar } from './components/common/UploadProgressBar.vue'
// Heavy components - async loaded (includes tiptap/monaco libraries)
export const RichTextEditor = defineAsyncComponent(() =>
    import('./components/common/RichTextEditor.vue')
)
export const MonacoEditor = defineAsyncComponent(() =>
    import('./components/common/MonacoEditor.vue')
)

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
// Heavy component - async loaded
export const JsonEditor = defineAsyncComponent(() =>
    import('./components/form/JsonEditor.vue')
)
export { default as FormGroup } from './components/form/FormGroup.vue'
export { default as FormLabel } from './components/form/FormLabel.vue'
export { default as FormError } from './components/form/FormError.vue'
export { default as FormSuccess } from './components/form/FormSuccess.vue'
export { default as FormHelpText } from './components/form/FormHelpText.vue'
export { default as FormSection } from './components/form/FormSection.vue'
export { default as FormActions } from './components/form/FormActions.vue'
export { default as TagInput } from './components/form/TagInput.vue'
// Heavy component - async loaded (large icon set)
export const IconPicker = defineAsyncComponent(() =>
    import('./components/form/IconPicker.vue')
)
export { default as MultiSelectServer } from './components/form/MultiSelectServer.vue'

// Services
export { resourceService } from './services/resourceService.js'
export { panelService } from './services/panelService.js'
export { authService } from './services/authService.js'
export { settingsService } from './services/settingsService.js'
export { mediaService } from './services/mediaService.js'
export { impersonationService } from './services/impersonationService.js'
export { permissionService } from './services/permissionService.js'
export { activityService } from './services/activityService.js'
export { searchService } from './services/searchService.js'
export { cardService } from './services/cardService.js'

// Stores
export { useAuthStore } from './stores/auth.js'
export { usePanelStore } from './stores/panel.js'
export { useDialogStore } from './stores/dialog.js'
export { useToastStore } from './stores/toast.js'
export { useSettingsStore } from './stores/settings.js'

// Router Utilities
export {
    generatePanelRoutes,
    loadDynamicPanelRoutes,
    createPanelGuard,
    generateStaticPanelRoutes,
    getPanelFromRoute,
    getResourceFromRoute
} from './router/panelRoutes.js'

// Composables
export { useToast } from './composables/useToast.js'
export { useDialog } from './composables/useDialog.js'
export { useModal } from './composables/useModal.js'
export { usePasswordToggle } from './composables/usePasswordToggle.js'
export { useImageEditor } from './composables/useImageEditor.js'
export { useImageEditHistory } from './composables/useImageEditHistory.js'
export { useLightbox } from './composables/useLightbox.js'
export { useUploadProgress } from './composables/useUploadProgress.js'
export { usePermissions, useRolePermissions, useAllPermissions } from './composables/usePermissions.js'
export { useGlobalSearch } from './composables/useGlobalSearch.js'
export { useCards } from './composables/useCards.js'
export { useSidebar, useEscapeKey, useClickOutside } from './composables/useSidebar.js'
export { useContextRoutes } from './composables/useContextRoutes.js'
export {
    useAuthForm,
    useLoginForm,
    useRegisterForm,
    useForgotPasswordForm,
    useResetPasswordForm,
    useChangePasswordForm
} from './composables/useAuthForms.js'

// Directives
export { default as tooltipDirective } from './directives/tooltip.js'

// Utilities
export * from './utils/validationRules.js'
export * from './utils/validationMessages.js'
export * from './utils/validationSchemas.js'
export * from './utils/laravelErrorMapper.js'
export * from './utils/httpErrorHandler.js'
export * from './utils/imageManipulation.js'
export * from './utils/memoization.js'
export * from './utils/debouncedValidation.js'
export * from './utils/lazyValidation.js'

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
        app.component('PanelLayout', () => import('./components/layout/PanelLayout.vue'))
        app.component('PanelSwitcher', () => import('./components/layout/PanelSwitcher.vue'))

        // Admin Layout components
        app.component('Sidebar', () => import('./components/layouts/Sidebar.vue'))
        app.component('MiniSidebar', () => import('./components/layouts/MiniSidebar.vue'))
        app.component('CompactSidebar', () => import('./components/layouts/CompactSidebar.vue'))
        app.component('Navbar', () => import('./components/layouts/Navbar.vue'))
        app.component('HorizontalNav', () => import('./components/layouts/HorizontalNav.vue'))
        app.component('NavItem', () => import('./components/layouts/NavItem.vue'))
        app.component('UserDropdown', () => import('./components/layouts/UserDropdown.vue'))
        app.component('DarkModeToggle', () => import('./components/layouts/DarkModeToggle.vue'))

        // Permission components
        app.component('PermissionGuard', () => import('./components/permissions/PermissionGuard.vue'))
        app.component('RolePermissionMatrix', () => import('./components/permissions/RolePermissionMatrix.vue'))

        // Activity components
        app.component('ActivityTimeline', () => import('./components/activity/ActivityTimeline.vue'))
        app.component('ActivityItem', () => import('./components/activity/ActivityItem.vue'))
        app.component('ActivityDiff', () => import('./components/activity/ActivityDiff.vue'))
        app.component('ActivityDetailsModal', () => import('./components/activity/ActivityDetailsModal.vue'))

        // Search components
        app.component('SearchPalette', () => import('./components/search/SearchPalette.vue'))
        app.component('SearchResultItem', () => import('./components/search/SearchResultItem.vue'))

        // Card/Widget components
        app.component('CardGrid', () => import('./components/cards/CardGrid.vue'))
        app.component('ValueCard', () => import('./components/cards/ValueCard.vue'))
        app.component('TrendCard', () => import('./components/cards/TrendCard.vue'))
        app.component('PartitionCard', () => import('./components/cards/PartitionCard.vue'))
        app.component('TableCard', () => import('./components/cards/TableCard.vue'))
        app.component('ChartCard', () => import('./components/cards/ChartCard.vue'))

        // Register tooltip directive
        app.directive('tooltip', tooltipDirective)

        // Optionally provide global config
        if (options.apiPrefix) {
            app.provide('studio-api-prefix', options.apiPrefix)
        }
    }
}
