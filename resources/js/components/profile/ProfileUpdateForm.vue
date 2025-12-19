<template>
  <form @submit.prevent="$emit('submit')" class="space-y-4">
    <!-- Name Field -->
    <div>
      <label :for="nameFieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
        {{ nameLabel }}
      </label>
      <input
        :id="nameFieldId"
        :value="form.name"
        @input="$emit('update:field', 'name', $event.target.value)"
        type="text"
        :required="nameRequired"
        class="auth-input"
        :class="{ error: errors.name }"
        :placeholder="namePlaceholder"
        autocomplete="name"
      />
      <div v-if="errors.name" class="auth-error">
        {{ errors.name }}
      </div>
    </div>

    <!-- Email Field -->
    <div>
      <label :for="emailFieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
        {{ emailLabel }}
      </label>
      <input
        :id="emailFieldId"
        :value="form.email"
        @input="$emit('update:field', 'email', $event.target.value)"
        type="email"
        :required="emailRequired"
        class="auth-input"
        :class="{ error: errors.email }"
        :placeholder="emailPlaceholder"
        autocomplete="email"
      />
      <div v-if="errors.email" class="auth-error">
        {{ errors.email }}
      </div>
    </div>

    <!-- Custom fields slot -->
    <slot name="additional-fields" />

    <!-- Success Message -->
    <div v-if="successMessage" class="auth-success bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
      <p class="text-sm font-medium text-green-600 dark:text-green-400">{{ successMessage }}</p>
    </div>

    <!-- Error Message -->
    <div v-if="errorMessage" class="auth-error bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
      <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ errorMessage }}</p>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end pt-2">
      <slot name="submit-button">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="auth-button-primary"
        >
          <span v-if="isSubmitting" class="flex items-center justify-center">
            <Icon name="loading" :size="20" class="animate-spin mr-2" />
            {{ submittingText }}
          </span>
          <span v-else>{{ submitText }}</span>
        </button>
      </slot>
    </div>
  </form>
</template>

<script setup>
import Icon from '../common/Icon.vue'

defineProps({
  form: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  successMessage: {
    type: String,
    default: '',
  },
  errorMessage: {
    type: String,
    default: '',
  },
  nameLabel: {
    type: String,
    default: 'Full Name',
  },
  namePlaceholder: {
    type: String,
    default: 'Enter your full name',
  },
  nameRequired: {
    type: Boolean,
    default: true,
  },
  nameFieldId: {
    type: String,
    default: 'name',
  },
  emailLabel: {
    type: String,
    default: 'Email Address',
  },
  emailPlaceholder: {
    type: String,
    default: 'Enter your email address',
  },
  emailRequired: {
    type: Boolean,
    default: true,
  },
  emailFieldId: {
    type: String,
    default: 'email',
  },
  submitText: {
    type: String,
    default: 'Update Profile',
  },
  submittingText: {
    type: String,
    default: 'Updating...',
  },
})

defineEmits(['submit', 'update:field'])
</script>
