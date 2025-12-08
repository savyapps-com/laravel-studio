import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    build: {
        lib: {
            entry: resolve(__dirname, 'resources/js/index.js'),
            name: 'LaravelStudio',
            fileName: (format) => `laravel-studio.${format}.js`
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
                assetFileNames: 'laravel-studio.css'
            }
        }
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js')
        }
    }
})
