<template>
  <div class="role-permission-matrix">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      <span class="ml-3 text-gray-600 dark:text-gray-400">Loading permissions...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-red-500 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
      {{ error }}
    </div>

    <!-- Permissions Matrix -->
    <div v-else>
      <!-- Actions Bar -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
          <button
            type="button"
            @click="selectAll"
            class="text-sm text-primary hover:text-primary-dark"
          >
            Select All
          </button>
          <button
            type="button"
            @click="deselectAll"
            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
          >
            Deselect All
          </button>
        </div>

        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ selectedCount }} of {{ totalCount }} permissions selected
        </div>
      </div>

      <!-- Permission Groups -->
      <div class="space-y-6">
        <div
          v-for="(permissions, group) in groupedPermissions"
          :key="group"
          class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <!-- Group Header -->
          <div
            class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 cursor-pointer"
            @click="toggleGroup(group)"
          >
            <div class="flex items-center gap-3">
              <svg
                class="w-4 h-4 transition-transform"
                :class="{ 'rotate-90': expandedGroups.includes(group) }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
              <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ group }}</h3>
              <span class="text-sm text-gray-500 dark:text-gray-400">
                ({{ getGroupSelectedCount(permissions) }}/{{ permissions.length }})
              </span>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click.stop="selectGroup(permissions)"
                class="text-xs text-primary hover:text-primary-dark px-2 py-1 rounded"
              >
                Select All
              </button>
              <button
                type="button"
                @click.stop="deselectGroup(permissions)"
                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 px-2 py-1 rounded"
              >
                Deselect All
              </button>
            </div>
          </div>

          <!-- Group Permissions -->
          <div
            v-show="expandedGroups.includes(group)"
            class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
          >
            <label
              v-for="permission in permissions"
              :key="permission.name"
              class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
              :class="{
                'bg-primary-50 dark:bg-primary-900/20 border-primary-300 dark:border-primary-700': isSelected(permission.name)
              }"
            >
              <input
                type="checkbox"
                :checked="isSelected(permission.name)"
                @change="togglePermission(permission.name)"
                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-700"
              />
              <div class="flex-1 min-w-0">
                <div class="font-medium text-sm text-gray-900 dark:text-gray-100">
                  {{ permission.display_name }}
                </div>
                <div v-if="permission.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                  {{ permission.description }}
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">
                  {{ permission.name }}
                </div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="Object.keys(groupedPermissions).length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
        No permissions available
      </div>

      <!-- Save Button -->
      <div v-if="showSaveButton" class="mt-6 flex justify-end">
        <button
          type="button"
          @click="savePermissions"
          :disabled="saving"
          class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <span v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
          {{ saving ? 'Saving...' : 'Save Permissions' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * RolePermissionMatrix Component
 *
 * A comprehensive UI for managing role permissions in a matrix format.
 * Displays permissions grouped by resource/category with checkboxes.
 *
 * @example
 * <!-- Basic usage -->
 * <RolePermissionMatrix
 *   :role-id="1"
 *   @saved="onPermissionsSaved"
 * />
 *
 * @example
 * <!-- With v-model for selected permissions -->
 * <RolePermissionMatrix
 *   v-model="selectedPermissions"
 *   :grouped-permissions="availablePermissions"
 * />
 *
 * @example
 * <!-- Auto-load and save -->
 * <RolePermissionMatrix
 *   :role-id="roleId"
 *   auto-load
 *   show-save-button
 *   @saved="handleSaved"
 *   @error="handleError"
 * />
 */

import { ref, computed, watch, onMounted } from 'vue'
import { useRolePermissions, useAllPermissions } from '../../composables/usePermissions'
import { permissionService } from '../../services/permissionService'

const props = defineProps({
  /**
   * Role ID to load/save permissions for
   */
  roleId: {
    type: [Number, String],
    default: null
  },

  /**
   * v-model: Currently selected permission names
   */
  modelValue: {
    type: Array,
    default: () => []
  },

  /**
   * Pre-loaded grouped permissions (if not using auto-load)
   */
  groupedPermissions: {
    type: Object,
    default: null
  },

  /**
   * Auto-load permissions on mount
   */
  autoLoad: {
    type: Boolean,
    default: true
  },

  /**
   * Show the save button
   */
  showSaveButton: {
    type: Boolean,
    default: true
  },

  /**
   * Initially expanded groups
   */
  initiallyExpanded: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue', 'saved', 'error', 'change'])

// State
const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const internalGroupedPermissions = ref({})
const selectedPermissions = ref([])
const expandedGroups = ref([])

// Composables for loading
const { loadRolePermissions, rolePermissions } = useRolePermissions()
const { loadAllPermissions, groupedPermissions: allGroupedPermissions } = useAllPermissions()

// Use external or internal grouped permissions
const displayedGroupedPermissions = computed(() => {
  return props.groupedPermissions || internalGroupedPermissions.value
})

// Total and selected counts
const totalCount = computed(() => {
  return Object.values(displayedGroupedPermissions.value)
    .reduce((sum, perms) => sum + perms.length, 0)
})

const selectedCount = computed(() => {
  return selectedPermissions.value.length
})

// Sync with v-model
watch(() => props.modelValue, (newVal) => {
  selectedPermissions.value = [...newVal]
}, { immediate: true, deep: true })

watch(selectedPermissions, (newVal) => {
  emit('update:modelValue', newVal)
  emit('change', newVal)
}, { deep: true })

// Methods
const isSelected = (permission) => {
  return selectedPermissions.value.includes(permission)
}

const togglePermission = (permission) => {
  const index = selectedPermissions.value.indexOf(permission)
  if (index > -1) {
    selectedPermissions.value.splice(index, 1)
  } else {
    selectedPermissions.value.push(permission)
  }
}

const selectAll = () => {
  const allPerms = Object.values(displayedGroupedPermissions.value)
    .flat()
    .map(p => p.name)
  selectedPermissions.value = [...new Set(allPerms)]
}

const deselectAll = () => {
  selectedPermissions.value = []
}

const selectGroup = (permissions) => {
  const permNames = permissions.map(p => p.name)
  const newSelected = [...new Set([...selectedPermissions.value, ...permNames])]
  selectedPermissions.value = newSelected
}

const deselectGroup = (permissions) => {
  const permNames = permissions.map(p => p.name)
  selectedPermissions.value = selectedPermissions.value.filter(p => !permNames.includes(p))
}

const getGroupSelectedCount = (permissions) => {
  return permissions.filter(p => isSelected(p.name)).length
}

const toggleGroup = (group) => {
  const index = expandedGroups.value.indexOf(group)
  if (index > -1) {
    expandedGroups.value.splice(index, 1)
  } else {
    expandedGroups.value.push(group)
  }
}

const loadData = async () => {
  loading.value = true
  error.value = null

  try {
    // Load all permissions if not provided
    if (!props.groupedPermissions) {
      await loadAllPermissions()
      internalGroupedPermissions.value = allGroupedPermissions.value
    }

    // Load role permissions if roleId is provided
    if (props.roleId) {
      await loadRolePermissions(props.roleId)
      selectedPermissions.value = [...rolePermissions.value]
    }

    // Expand all groups by default if none specified
    if (props.initiallyExpanded.length > 0) {
      expandedGroups.value = [...props.initiallyExpanded]
    } else {
      expandedGroups.value = Object.keys(displayedGroupedPermissions.value)
    }
  } catch (err) {
    error.value = err.message || 'Failed to load data'
    emit('error', err)
  } finally {
    loading.value = false
  }
}

const savePermissions = async () => {
  if (!props.roleId) {
    emit('saved', selectedPermissions.value)
    return
  }

  saving.value = true
  error.value = null

  try {
    await permissionService.updateRolePermissions(props.roleId, selectedPermissions.value)
    emit('saved', selectedPermissions.value)
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save permissions'
    emit('error', err)
  } finally {
    saving.value = false
  }
}

// Initialize
onMounted(() => {
  if (props.autoLoad) {
    loadData()
  }
})

// Watch for roleId changes
watch(() => props.roleId, (newVal, oldVal) => {
  if (newVal !== oldVal && props.autoLoad) {
    loadData()
  }
})

// Expose methods for parent component
defineExpose({
  loadData,
  savePermissions,
  selectAll,
  deselectAll
})
</script>

<style scoped>
.role-permission-matrix {
  @apply w-full;
}
</style>
