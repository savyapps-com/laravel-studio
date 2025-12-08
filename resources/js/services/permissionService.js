/**
 * Permission Service
 * Handles all API calls for permission management
 */

export const permissionService = {
  /**
   * Get all permissions with grouping
   * @returns {Promise} { grouped: Object, permissions: Array }
   */
  async getAllPermissions() {
    const response = await window.axios.get('/api/permissions')
    return response.data
  },

  /**
   * Get current user's permissions
   * @returns {Promise} { permissions: Array, is_super_admin: boolean }
   */
  async getMyPermissions() {
    const response = await window.axios.get('/api/permissions/my')
    return response.data
  },

  /**
   * Check if current user has a specific permission
   * @param {string} permission - Permission name to check
   * @returns {Promise} { permission: string, allowed: boolean }
   */
  async checkPermission(permission) {
    const response = await window.axios.post('/api/permissions/check', {
      permission
    })
    return response.data
  },

  /**
   * Check if current user has multiple permissions
   * @param {Array} permissions - Array of permission names to check
   * @param {string} mode - 'any' or 'all'
   * @returns {Promise} { permissions: Array, mode: string, allowed: boolean }
   */
  async checkPermissions(permissions, mode = 'any') {
    const response = await window.axios.post('/api/permissions/check', {
      permissions,
      mode
    })
    return response.data
  },

  /**
   * Sync permissions from resources
   * @returns {Promise} { message: string, count: number, permissions: Array }
   */
  async syncPermissions() {
    const response = await window.axios.post('/api/permissions/sync')
    return response.data
  },

  /**
   * Get permissions for a specific role
   * @param {number} roleId - Role ID
   * @returns {Promise} { role_id: number, role_name: string, permissions: Array }
   */
  async getRolePermissions(roleId) {
    const response = await window.axios.get(`/api/roles/${roleId}/permissions`)
    return response.data
  },

  /**
   * Update permissions for a specific role
   * @param {number} roleId - Role ID
   * @param {Array} permissions - Array of permission names
   * @returns {Promise} { message: string, role_id: number, permissions: Array }
   */
  async updateRolePermissions(roleId, permissions) {
    const response = await window.axios.put(`/api/roles/${roleId}/permissions`, {
      permissions
    })
    return response.data
  },

  /**
   * Build a permission name from resource and action
   * @param {string} resource - Resource key
   * @param {string} action - Action (list, view, create, update, delete)
   * @returns {string}
   */
  buildPermissionName(resource, action) {
    return `${resource}.${action}`
  },

  /**
   * Parse a permission name into resource and action
   * @param {string} permission - Permission name
   * @returns {Object} { resource: string, action: string }
   */
  parsePermissionName(permission) {
    const parts = permission.split('.')
    return {
      resource: parts[0] || '',
      action: parts.slice(1).join('.') || ''
    }
  }
}

export default permissionService
