// Export Tailwind plugin that users add to their config
module.exports = function() {
    return {
        content: [
            './vendor/savyapps-com/laravel-studio/dist/**/*.js',
        ],
        theme: {
            extend: {
                // Package-specific theme extensions
            }
        }
    }
}
