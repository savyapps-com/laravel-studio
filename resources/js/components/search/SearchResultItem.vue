<template>
  <li
    :class="[
      'group flex cursor-pointer select-none items-center rounded-md px-3 py-2',
      selected
        ? 'bg-primary-600 text-white'
        : 'text-gray-700 hover:bg-gray-100'
    ]"
    @click="$emit('select', result)"
  >
    <!-- Avatar or Icon -->
    <div
      v-if="result.avatar"
      class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full"
    >
      <img
        :src="result.avatar"
        :alt="result.title"
        class="h-full w-full object-cover"
      />
    </div>
    <div
      v-else-if="result.icon"
      :class="[
        'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full',
        selected ? 'bg-primary-500' : 'bg-gray-100'
      ]"
    >
      <span
        :class="['h-4 w-4', selected ? 'text-white' : 'text-gray-500']"
        v-html="result.icon"
      ></span>
    </div>
    <div
      v-else
      :class="[
        'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-medium',
        selected ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600'
      ]"
    >
      {{ initials }}
    </div>

    <!-- Content -->
    <div class="ml-3 flex-1 truncate">
      <div class="flex items-center">
        <!-- Title -->
        <span
          :class="['truncate font-medium', selected ? 'text-white' : 'text-gray-900']"
          v-html="highlightedTitle"
        ></span>

        <!-- ID Badge -->
        <span
          v-if="result.id"
          :class="[
            'ml-2 inline-flex items-center rounded px-1.5 py-0.5 text-xs',
            selected
              ? 'bg-primary-500 text-white'
              : 'bg-gray-100 text-gray-500'
          ]"
        >
          #{{ result.id }}
        </span>
      </div>

      <!-- Subtitle -->
      <p
        v-if="result.subtitle"
        :class="['truncate text-sm', selected ? 'text-primary-100' : 'text-gray-500']"
        v-html="highlightedSubtitle"
      ></p>

      <!-- Meta -->
      <p
        v-if="result.meta"
        :class="['truncate text-xs', selected ? 'text-primary-200' : 'text-gray-400']"
      >
        {{ result.meta }}
      </p>
    </div>

    <!-- Arrow indicator -->
    <svg
      :class="[
        'ml-2 h-4 w-4 flex-shrink-0',
        selected ? 'text-white' : 'text-gray-400 opacity-0 group-hover:opacity-100'
      ]"
      viewBox="0 0 20 20"
      fill="currentColor"
    >
      <path
        fill-rule="evenodd"
        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
        clip-rule="evenodd"
      />
    </svg>
  </li>
</template>

<script setup>
import { computed } from 'vue'
import searchService from '../../services/searchService'

const props = defineProps({
  result: {
    type: Object,
    required: true
  },
  query: {
    type: String,
    default: ''
  },
  selected: {
    type: Boolean,
    default: false
  }
})

defineEmits(['select'])

const initials = computed(() => {
  if (!props.result.title) return '?'
  return props.result.title
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const highlightedTitle = computed(() => {
  if (!props.query || props.selected) {
    return props.result.title
  }
  return searchService.highlightMatches(props.result.title, props.query)
})

const highlightedSubtitle = computed(() => {
  if (!props.query || !props.result.subtitle || props.selected) {
    return props.result.subtitle
  }
  return searchService.highlightMatches(props.result.subtitle, props.query)
})
</script>
