/**
 * Bootstrap file - configures axios with Laravel Studio defaults
 */
import { configureAxios } from 'laravel-studio'

// Configure axios with default settings
configureAxios({
  tokenKey: 'auth_token',
  loginPath: '/admin',
  showSuccessToasts: true,
  showErrorToasts: true
})
