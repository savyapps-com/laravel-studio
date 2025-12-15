import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    build: {
        lib: {
            // Multiple entry points for tree-shaking and selective imports
            entry: {
                'laravel-studio': resolve(__dirname, 'resources/js/index.js'),
                'core': resolve(__dirname, 'resources/js/entries/core.js'),
                'activity': resolve(__dirname, 'resources/js/entries/activity.js'),
                'cards': resolve(__dirname, 'resources/js/entries/cards.js'),
                'search': resolve(__dirname, 'resources/js/entries/search.js'),
            },
            name: 'LaravelStudio',
            fileName: (format, entryName) => `${entryName}.${format}.js`
        },
        rollupOptions: {
            // Externalize deps that shouldn't be bundled
            external: ['vue', 'axios', 'pinia', 'vue-router', 'dompurify', 'vee-validate', 'vue-advanced-cropper', 'vue-advanced-cropper/dist/style.css'],
            output: {
                // Global variable name for UMD build
                globals: {
                    vue: 'Vue',
                    axios: 'axios',
                    pinia: 'Pinia',
                    'vue-router': 'VueRouter',
                    dompurify: 'DOMPurify',
                    'vee-validate': 'VeeValidate',
                    'vue-advanced-cropper': 'VueAdvancedCropper'
                },
                // Export CSS separately
                assetFileNames: 'laravel-studio.css',
                // Manual chunks for better code-splitting
                manualChunks: (id) => {
                    // Heavy components get their own chunks
                    if (id.includes('ImageEditor.vue')) return 'image-editor'
                    if (id.includes('JsonEditor.vue')) return 'json-editor'
                    if (id.includes('IconPicker.vue')) return 'icon-picker'
                    if (id.includes('RolePermissionMatrix.vue')) return 'permissions'
                    if (id.includes('vue-advanced-cropper')) return 'cropper'
                }
            }
        }
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js')
        }
    }
})
