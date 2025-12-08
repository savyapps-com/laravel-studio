import { ref, computed, readonly } from 'vue'
import { permissionService } from '../services/permissionService'

// Shared state for permissions (singleton pattern)
const userPermissions = ref([])
const isSuperAdmin = ref(false)
const isLoaded = ref(false)
const isLoading = ref(false)

/**
 * Composable for managing and checking user permissions
 *
 * @example
 * // Basic usage
 * const { can, canAny, canAll, loadPermissions, permissions } = usePermissions()
 *
 * // Load permissions on app init
 * await loadPermissions()
 *
 * // Check single permission
 * if (can('users.create')) {
 *   // User can create users
 * }
 *
 * // Check any of multiple permissions
 * if (canAny(['users.create', 'users.update'])) {
 *   // User has at least one of these permissions
 * }
 *
 * // Check all permissions
 * if (canAll(['users.create', 'users.update'])) {
 *   // User has all of these permissions
 * }
 *
 * // Check resource permission
 * if (canResource('users', 'create')) {
 *   // User can create users
 * }
 */
export function usePermissions() {
  /**
   * Load user permissions from the server
   * @param {boolean} force - Force reload even if already loaded
   * @returns {Promise<void>}
   */
  const loadPermissions = async (force = false) => {
    if (isLoaded.value && !force) {
      return
    }

    if (isLoading.value) {
      return
    }

    isLoading.value = true

    try {
      const response = await permissionService.getMyPermissions()
      userPermissions.value = response.permissions || []
      isSuperAdmin.value = response.is_super_admin || false
      isLoaded.value = true
    } catch (error) {
      console.error('Failed to load permissions:', error)
      userPermissions.value = []
      isSuperAdmin.value = false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Clear cached permissions (e.g., on logout)
   */
  const clearPermissions = () => {
    userPermissions.value = []
    isSuperAdmin.value = false
    isLoaded.value = false
  }

  /**
   * Check if user has a specific permission
   * @param {string} permission - Permission name to check
   * @returns {boolean}
   */
  const can = (permission) => {
    if (isSuperAdmin.value) {
      return true
    }
    return userPermissions.value.includes(permission)
  }

  /**
   * Check if user has any of the given permissions
   * @param {Array<string>} permissions - Array of permission names
   * @returns {boolean}
   */
  const canAny = (permissions) => {
    if (isSuperAdmin.value) {
      return true
    }
    return permissions.some(permission => userPermissions.value.includes(permission))
  }

  /**
   * Check if user has all of the given permissions
   * @param {Array<string>} permissions - Array of permission names
   * @returns {boolean}
   */
  const canAll = (permissions) => {
    if (isSuperAdmin.value) {
      return true
    }
    return permissions.every(permission => userPermissions.value.includes(permission))
  }

  /**
   * Check if user can perform action on a resource
   * @param {string} resource - Resource key (e.g., 'users')
   * @param {string} action - Action (e.g., 'create', 'update', 'delete')
   * @returns {boolean}
   */
  const canResource = (resource, action) => {
    return can(`${resource}.${action}`)
  }

  /**
   * Check if user can view resource list
   * @param {string} resource - Resource key
   * @returns {boolean}
   */
  const canViewList = (resource) => {
    return canResource(resource, 'list')
  }

  /**
   * Check if user can view resource details
   * @param {string} resource - Resource key
   * @returns {boolean}
   */
  const canView = (resource) => {
    return canResource(resource, 'view')
  }

  /**
   * Check if user can create resource
   * @param {string} resource - Resource key
   * @returns {boolean}
   */
  const canCreate = (resource) => {
    return canResource(resource, 'create')
  }

  /**
   * Check if user can update resource
   * @param {string} resource - Resource key
   * @returns {boolean}
   */
  const canUpdate = (resource) => {
    return canResource(resource, 'update')
  }

  /**
   * Check if user can delete resource
   * @param {string} resource - Resource key
   * @returns {boolean}
   */
  const canDelete = (resource) => {
    return canResource(resource, 'delete')
  }

  // Computed properties
  const permissions = computed(() => readonly(userPermissions.value))
  const hasPermissions = computed(() => userPermissions.value.length > 0 || isSuperAdmin.value)

  return {
    // State
    permissions,
    isSuperAdmin: readonly(isSuperAdmin),
    isLoaded: readonly(isLoaded),
    isLoading: readonly(isLoading),
    hasPermissions,

    // Methods
    loadPermissions,
    clearPermissions,
    can,
    canAny,
    canAll,
    canResource,
    canViewList,
    canView,
    canCreate,
    canUpdate,
    canDelete
  }
}

/**
 * Composable for managing role permissions
 *
 * @example
 * const { rolePermissions, loading, loadRolePermissions, updateRolePermissions } = useRolePermissions()
 *
 * // Load permissions for a role
 * await loadRolePermissions(1)
 *
 * // Update role permissions
 * await updateRolePermissions(1, ['users.list', 'users.view'])
 */
export function useRolePermissions() {
  const rolePermissions = ref([])
  const loading = ref(false)
  const error = ref(null)

  /**
   * Load permissions for a specific role
   * @param {number} roleId - Role ID
   * @returns {Promise<void>}
   */
  const loadRolePermissions = async (roleId) => {
    loading.value = true
    error.value = null

    try {
      const response = await permissionService.getRolePermissions(roleId)
      rolePermissions.value = response.permissions || []
    } catch (err) {
      console.error('Failed to load role permissions:', err)
      error.value = err.response?.data?.message || 'Failed to load permissions'
      rolePermissions.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Update permissions for a specific role
   * @param {number} roleId - Role ID
   * @param {Array<string>} permissions - Array of permission names
   * @returns {Promise<boolean>}
   */
  const updateRolePermissions = async (roleId, permissions) => {
    loading.value = true
    error.value = null

    try {
      const response = await permissionService.updateRolePermissions(roleId, permissions)
      rolePermissions.value = response.permissions || []
      return true
    } catch (err) {
      console.error('Failed to update role permissions:', err)
      error.value = err.response?.data?.message || 'Failed to update permissions'
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Toggle a permission in the current list
   * @param {string} permission - Permission name
   */
  const togglePermission = (permission) => {
    const index = rolePermissions.value.indexOf(permission)
    if (index > -1) {
      rolePermissions.value.splice(index, 1)
    } else {
      rolePermissions.value.push(permission)
    }
  }

  /**
   * Check if role has a specific permission
   * @param {string} permission - Permission name
   * @returns {boolean}
   */
  const hasPermission = (permission) => {
    return rolePermissions.value.includes(permission)
  }

  return {
    rolePermissions,
    loading: readonly(loading),
    error: readonly(error),
    loadRolePermissions,
    updateRolePermissions,
    togglePermission,
    hasPermission
  }
}

/**
 * Composable for loading all available permissions
 *
 * @example
 * const { allPermissions, groupedPermissions, loading, loadAllPermissions } = useAllPermissions()
 *
 * await loadAllPermissions()
 *
 * // Access grouped permissions for UI
 * Object.entries(groupedPermissions.value).forEach(([group, perms]) => {
 *   console.log(group, perms)
 * })
 */
export function useAllPermissions() {
  const allPermissions = ref([])
  const groupedPermissions = ref({})
  const loading = ref(false)
  const error = ref(null)

  /**
   * Load all permissions from the server
   * @returns {Promise<void>}
   */
  const loadAllPermissions = async () => {
    loading.value = true
    error.value = null

    try {
      const response = await permissionService.getAllPermissions()
      allPermissions.value = response.permissions || []
      groupedPermissions.value = response.grouped || {}
    } catch (err) {
      console.error('Failed to load all permissions:', err)
      error.value = err.response?.data?.message || 'Failed to load permissions'
      allPermissions.value = []
      groupedPermissions.value = {}
    } finally {
      loading.value = false
    }
  }

  return {
    allPermissions: readonly(allPermissions),
    groupedPermissions: readonly(groupedPermissions),
    loading: readonly(loading),
    error: readonly(error),
    loadAllPermissions
  }
}

export default usePermissions
