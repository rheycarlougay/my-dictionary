<script setup>
import { useRouter, useRoute } from 'vue-router'
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth.js'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const currentTab = computed(() => route.name)

function navigateToTab(tabName) {
  router.push({ name: tabName })
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
          <div class="flex space-x-8">
          <button
            @click="navigateToTab('Dictionary')"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
              currentTab === 'Dictionary'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
              </svg>
              Dictionary
            </div>
          </button>
          
          <button
            @click="navigateToTab('Favorites')"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
              currentTab === 'Favorites'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
              </svg>
              Favorites
            </div>
          </button>
          </div>
          
          <div class="flex items-center gap-4">
            <span class="text-sm text-gray-700">{{ authStore.getUser?.name }}</span>
            <button
              @click="handleLogout"
              class="text-gray-500 hover:text-gray-700 transition-colors duration-200"
              title="Logout"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <slot />
  </div>
</template>

<style scoped>
</style>
