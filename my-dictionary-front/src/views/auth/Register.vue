<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="bg-white border border-gray-200 rounded-lg shadow p-8 w-full max-w-sm">
      <div class="flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-gray-500 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" fill="none"/>
          <line x1="8" y1="8" x2="16" y2="8" stroke="currentColor"/>
          <line x1="8" y1="12" x2="16" y2="12" stroke="currentColor"/>
          <line x1="8" y1="16" x2="12" y2="16" stroke="currentColor"/>
        </svg>
        <span class="text-2xl font-bold text-gray-700">Dictionary Register</span>
      </div>
      <form @submit.prevent="handleRegister" class="space-y-4">
        <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ errorMessage }}
        </div>
        
        <div>
          <label class="block text-gray-700 font-medium mb-1">Name</label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-200 bg-gray-100"
            placeholder="Your full name"
          />
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-200 bg-gray-100"
            placeholder="you@example.com"
          />
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-200 bg-gray-100"
            placeholder="Enter your password"
          />
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-1">Confirm Password</label>
          <input
            v-model="confirmPassword"
            type="password"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-200 bg-gray-100"
            placeholder="Confirm your password"
          />
        </div>
        <button
          type="submit"
          :disabled="authStore.isLoading"
          class="w-full py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="authStore.isLoading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Registering...
          </span>
          <span v-else>Register</span>
        </button>
      </form>
      <div class="mt-5 text-center">
        <router-link to="/login" class="text-blue-600 hover:underline">
          Already have an account? Login
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const errorMessage = ref('')

async function handleRegister() {
  if (!name.value || !email.value || !password.value || !confirmPassword.value) {
    errorMessage.value = 'Please fill in all fields'
    return
  }

  if (password.value !== confirmPassword.value) {
    errorMessage.value = 'Passwords do not match'
    return
  }

  if (password.value.length < 8) {
    errorMessage.value = 'Password must be at least 8 characters long'
    return
  }

  errorMessage.value = ''
  
  const result = await authStore.register({
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: confirmPassword.value
  })

          if (result.success) {
          router.push('/dictionary')
        } else {
    errorMessage.value = result.message
  }
}
</script>