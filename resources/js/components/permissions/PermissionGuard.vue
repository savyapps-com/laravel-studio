<template>
  <template v-if="shouldRender">
    <slot />
  </template>
  <template v-else-if="$slots.fallback">
    <slot name="fallback" />
  </template>
</template>

<script setup>
/**
 * PermissionGuard Component
 *
 * A renderless component that conditionally renders its content based on user permissions.
 *
 * @example
 * <!-- Single permission check -->
 * <PermissionGuard permission="users.create">
 *   <button>Create User</button>
 * </PermissionGuard>
 *
 * @example
 * <!-- Multiple permissions (any) -->
 * <PermissionGuard :permissions="['users.create', 'users.update']">
 *   <button>Save</button>
 * </PermissionGuard>
 *
 * @example
 * <!-- Multiple permissions (all required) -->
 * <PermissionGuard :permissions="['users.create', 'roles.assign']" require-all>
 *   <button>Create Admin</button>
 * </PermissionGuard>
 *
 * @example
 * <!-- With fallback content -->
 * <PermissionGuard permission="users.delete">
 *   <button>Delete</button>
 *   <template #fallback>
 *     <span>You don't have permission to delete</span>
 *   </template>
 * </PermissionGuard>
 *
 * @example
 * <!-- Resource permission shorthand -->
 * <PermissionGuard resource="users" action="create">
 *   <button>Create User</button>
 * </PermissionGuard>
 *
 * @example
 * <!-- Inverse check (hide if has permission) -->
 * <PermissionGuard permission="admin.bypass" inverse>
 *   <span>Only visible to non-admins</span>
 * </PermissionGuard>
 */

import { computed } from 'vue'
import { usePermissions } from '../../composables/usePermissions'

const props = defineProps({
  /**
   * Single permission to check
   */
  permission: {
    type: String,
    default: null
  },

  /**
   * Multiple permissions to check
   */
  permissions: {
    type: Array,
    default: () => []
  },

  /**
   * Resource key for resource permission shorthand
   */
  resource: {
    type: String,
    default: null
  },

  /**
   * Action for resource permission shorthand
   */
  action: {
    type: String,
    default: null
  },

  /**
   * When checking multiple permissions, require all to pass
   * Default is any (at least one permission)
   */
  requireAll: {
    type: Boolean,
    default: false
  },

  /**
   * Inverse the check (show if user does NOT have permission)
   */
  inverse: {
    type: Boolean,
    default: false
  }
})

const { can, canAny, canAll, canResource, isSuperAdmin } = usePermissions()

const shouldRender = computed(() => {
  let hasPermission = false

  // Check super admin first (they always have access)
  if (isSuperAdmin.value && !props.inverse) {
    return true
  }

  // Resource + action shorthand
  if (props.resource && props.action) {
    hasPermission = canResource(props.resource, props.action)
  }
  // Single permission
  else if (props.permission) {
    hasPermission = can(props.permission)
  }
  // Multiple permissions
  else if (props.permissions.length > 0) {
    hasPermission = props.requireAll
      ? canAll(props.permissions)
      : canAny(props.permissions)
  }
  // No permissions specified - always show
  else {
    hasPermission = true
  }

  // Apply inverse if needed
  return props.inverse ? !hasPermission : hasPermission
})
</script>
