<template>
  <div class="panels-resource-page">
    <ResourceManager
      resource="panels"
      :default-per-page="15"
      :enable-export="true"
      @created="handleCreated"
      @updated="handleUpdated"
      @deleted="handleDeleted"
    />
  </div>
</template>

<script setup>
import ResourceManager from 'laravel-studio/components/resource/ResourceManager.vue'

function handleCreated(data) {
  console.log('Panel created:', data)
}

function handleUpdated(data) {
  console.log('Panel updated:', data)
}

function handleDeleted(ids) {
  console.log('Panels deleted:', ids)
}
</script>
