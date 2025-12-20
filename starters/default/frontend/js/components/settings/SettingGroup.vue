<template>
  <SettingGroupCore v-bind="$attrs" @toggle="$emit('toggle', $event)">
    <slot />
  </SettingGroupCore>
</template>

<script setup>
import { SettingGroup as SettingGroupCore } from 'laravel-studio'

defineProps({
  title: String,
  description: String,
  collapsible: Boolean,
  collapsed: Boolean,
  showDivider: Boolean
})

defineEmits(['toggle'])
</script>
