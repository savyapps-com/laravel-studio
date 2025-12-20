<template>
  <LayoutOptionCore v-bind="$attrs" @select="$emit('select')">
    <slot />
  </LayoutOptionCore>
</template>

<script setup>
import { LayoutOption as LayoutOptionCore } from 'laravel-studio'

defineProps({
  layout: Object,
  isActive: Boolean
})

defineEmits(['select'])
</script>
