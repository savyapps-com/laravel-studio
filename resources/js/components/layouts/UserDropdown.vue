<template>
  <div class="relative">
    <button
      @click="toggleDropdown"
      class="flex items-center space-x-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200"
    >
      <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 dark:from-primary-600 dark:to-secondary-600 rounded-full flex items-center justify-center shadow-md">
        <slot name="avatar">
          <span class="text-white font-semibold text-sm">{{ user.initials }}</span>
        </slot>
      </div>
      <div class="hidden sm:block text-left">
        <p class="text-sm font-medium text-title leading-tight">{{ user.name }}</p>
        <p class="text-xs text-muted">{{ user.role }}</p>
      </div>
      <Icon
        name="chevron-down"
        :size="16"
        class="text-muted transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <!-- Profile dropdown -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
      @click.stop
    >
      <div class="py-2">
        <!-- Menu Items Slot or Default Items -->
        <slot name="menu-items" :closeDropdown="closeDropdown">
          <!-- Profile Link -->
          <router-link
            v-if="profileRoute"
            :to="profileRoute"
            @click="closeDropdown"
            class="block px-4 py-2 text-sm text-title hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
          >
            {{ profileLabel }}
          </router-link>

          <!-- Settings Link -->
          <router-link
            v-if="settingsRoute"
            :to="settingsRoute"
            @click="closeDropdown"
            class="block px-4 py-2 text-sm text-title hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
          >
            {{ settingsLabel }}
          </router-link>

          <!-- Custom Links -->
          <template v-for="link in customLinks" :key="link.label">
            <router-link
              v-if="link.to"
              :to="link.to"
              @click="closeDropdown"
              class="block px-4 py-2 text-sm text-title hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              {{ link.label }}
            </router-link>
            <button
              v-else-if="link.action"
              @click="() => { link.action(); closeDropdown(); }"
              class="block w-full text-left px-4 py-2 text-sm text-title hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
            >
              {{ link.label }}
            </button>
          </template>
        </slot>

        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

        <!-- Logout -->
        <button
          @click="handleLogout"
          class="block w-full text-left px-4 py-2 text-sm text-title hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
        >
          {{ logoutLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import Icon from '../common/Icon.vue'

export default {
  name: 'UserDropdown',
  components: {
    Icon,
  },
  emits: ['logout'],
  props: {
    user: {
      type: Object,
      required: true,
      // Expected: { name: String, role: String, initials: String }
    },
    profileRoute: {
      type: [String, Object],
      default: null,
    },
    settingsRoute: {
      type: [String, Object],
      default: null,
    },
    profileLabel: {
      type: String,
      default: 'Profile',
    },
    settingsLabel: {
      type: String,
      default: 'Settings',
    },
    logoutLabel: {
      type: String,
      default: 'Sign out',
    },
    customLinks: {
      type: Array,
      default: () => [],
      // Expected: [{ label: String, to: Object|String } | { label: String, action: Function }]
    },
  },
  setup(props, { emit }) {
    const isOpen = ref(false)

    const toggleDropdown = () => {
      isOpen.value = !isOpen.value
    }

    const closeDropdown = () => {
      isOpen.value = false
    }

    const handleLogout = () => {
      closeDropdown()
      emit('logout')
    }

    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (isOpen.value && !event.target.closest('.relative')) {
        closeDropdown()
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      isOpen,
      toggleDropdown,
      closeDropdown,
      handleLogout,
    }
  },
}
</script>
