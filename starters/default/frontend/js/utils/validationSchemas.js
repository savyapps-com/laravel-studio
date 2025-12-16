/**
 * Yup Validation Schemas for Forms
 */

import * as yup from 'yup'

/**
 * Login form validation schema
 */
export const loginSchema = yup.object({
  email: yup
    .string()
    .required('Email is required')
    .email('Please enter a valid email address'),
  password: yup
    .string()
    .required('Password is required'),
  remember: yup.boolean()
})

/**
 * Registration form validation schema
 */
export const registerSchema = yup.object({
  name: yup
    .string()
    .required('Name is required')
    .min(2, 'Name must be at least 2 characters'),
  email: yup
    .string()
    .required('Email is required')
    .email('Please enter a valid email address'),
  password: yup
    .string()
    .required('Password is required')
    .min(8, 'Password must be at least 8 characters'),
  password_confirmation: yup
    .string()
    .required('Please confirm your password')
    .oneOf([yup.ref('password')], 'Passwords must match')
})

/**
 * Forgot password form validation schema
 */
export const forgotPasswordSchema = yup.object({
  email: yup
    .string()
    .required('Email is required')
    .email('Please enter a valid email address')
})

/**
 * Reset password form validation schema
 */
export const resetPasswordSchema = yup.object({
  email: yup
    .string()
    .required('Email is required')
    .email('Please enter a valid email address'),
  password: yup
    .string()
    .required('Password is required')
    .min(8, 'Password must be at least 8 characters'),
  password_confirmation: yup
    .string()
    .required('Please confirm your password')
    .oneOf([yup.ref('password')], 'Passwords must match')
})

/**
 * Change password form validation schema
 */
export const changePasswordSchema = yup.object({
  current_password: yup
    .string()
    .required('Current password is required'),
  password: yup
    .string()
    .required('New password is required')
    .min(8, 'Password must be at least 8 characters'),
  password_confirmation: yup
    .string()
    .required('Please confirm your new password')
    .oneOf([yup.ref('password')], 'Passwords must match')
})

/**
 * Profile update form validation schema
 */
export const profileUpdateSchema = yup.object({
  name: yup
    .string()
    .required('Name is required')
    .min(2, 'Name must be at least 2 characters'),
  email: yup
    .string()
    .required('Email is required')
    .email('Please enter a valid email address')
})
