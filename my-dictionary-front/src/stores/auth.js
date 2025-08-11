import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authApi from '../api/auth.js'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const getUser = computed(() => user.value)

  async function login(credentials) {
    isLoading.value = true
    try {
      const response = await authApi.login(credentials)
      if (response.success) {
        token.value = response.data.token
        user.value = response.data.user
        localStorage.setItem('auth_token', response.data.token)
        return { success: true }
      }
      return { success: false, message: response.message }
    } catch (error) {
      console.error('Login error:', error)
      return { success: false, message: error.response?.data?.message || 'Login failed' }
    } finally {
      isLoading.value = false
    }
  }

  async function register(userData) {
    isLoading.value = true
    try {
      const response = await authApi.register(userData)
      if (response.success) {
        token.value = response.data.token
        user.value = response.data.user
        localStorage.setItem('auth_token', response.data.token)
        return { success: true }
      }
      return { success: false, message: response.message }
    } catch (error) {
      console.error('Registration error:', error)
      return { success: false, message: error.response?.data?.message || 'Registration failed' }
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function checkAuth() {
    if (!token.value) return false
    
    try {
      const response = await authApi.check()
      if (response.success && response.authenticated) {
        user.value = response.user
        return true
      }
      return false
    } catch (error) {
      console.error('Auth check error:', error)
      logout()
      return false
    }
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  return {
    user,
    token,
    isLoading,
    
    isAuthenticated,
    getUser,
    
    login,
    register,
    logout,
    checkAuth,
    clearAuth
  }
})
