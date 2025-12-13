<template>
  <div class="dynamic-resource-page">
    <ResourceManager
      :resource="resource"
      :panel="currentPanel"
      :default-per-page="15"
      :enable-export="true"
      @created="handleCreated"
      @updated="handleUpdated"
      @deleted="handleDeleted"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ResourceManager from 'laravel-studio/components/resource/ResourceManager.vue'

const route = useRoute()

// Get current panel and resource from route
const currentPanel = computed(() => route.params.panel)
const resource = computed(() => route.params.resource)

function handleCreated(data) {
  console.log('Resource created:', data)
}

function handleUpdated(data) {
  console.log('Resource updated:', data)
}

function handleDeleted(ids) {
  console.log('Resources deleted:', ids)
}
</script>
