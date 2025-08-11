<script setup>
import { ref, onMounted } from 'vue'
import homeApi from '../api/dictionary.js'
import Layout from '../components/Layout.vue'

const favorites = ref([])
const isLoading = ref(false)
const showEditModal = ref(false)
const currentFavorite = ref(null)
const editNoteText = ref('')
const expandedCards = ref(new Set())

// Confirmation dialog state
const showDeleteConfirmModal = ref(false)
const favoriteToDelete = ref(null)

// Toast notification state
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success') // 'success', 'error', 'info'

async function loadFavorites() {
  isLoading.value = true
  try {
    // In a real app, you'd call the API
    const response = await homeApi.getFavorites()
    
    if (response.status_code != 200) {
      favorites.value = []
      return
    }
    
    favorites.value = response.data || []
  } catch (error) {
    console.error('Error fetching favorites:', error)
    favorites.value = []
  } finally {
    isLoading.value = false
  }
}

function toggleCard(id) {
  if (expandedCards.value.has(id)) {
    expandedCards.value.delete(id)
  } else {
    expandedCards.value.add(id)
  }
}

function isCardExpanded(id) {
  return expandedCards.value.has(id)
}

function openEditModal(favorite) {
  currentFavorite.value = favorite
  editNoteText.value = favorite.note || ''
  showEditModal.value = true
}

function closeEditModal() {
  currentFavorite.value = null
  showEditModal.value = false
  editNoteText.value = ''
}

async function updateFavorite() {
  if (!currentFavorite.value) return
  
  try {
    // In a real app, you'd call the API
    await homeApi.updateFavorite(currentFavorite.value.id, editNoteText.value)
    
    // For now, update the dummy data
    const index = favorites.value.findIndex(f => f.id === currentFavorite.value.id)
    if (index !== -1) {
      favorites.value[index].note = editNoteText.value
      favorites.value[index].updated_at = new Date().toISOString()
    }
    
    closeEditModal()
    showToastNotification('Note updated successfully!', 'success')
  } catch (error) {
    console.error('Error updating favorite:', error)
    showToastNotification('Failed to update note. Please try again.', 'error')
  }
}

function openDeleteConfirmModal(favorite) {
  favoriteToDelete.value = favorite
  showDeleteConfirmModal.value = true
}

function closeDeleteConfirmModal() {
  favoriteToDelete.value = null
  showDeleteConfirmModal.value = false
}

async function confirmDelete() {
  if (!favoriteToDelete.value) return
  
  try {
    // In a real app, you'd call the API
    await homeApi.deleteFavorite(favoriteToDelete.value.id)
    
    // For now, remove from dummy data
    favorites.value = favorites.value.filter(f => f.id !== favoriteToDelete.value.id)
    // Also remove from expanded cards
    expandedCards.value.delete(favoriteToDelete.value.id)
    
    closeDeleteConfirmModal()
    showToastNotification('Word moved to trash successfully!', 'success')
  } catch (error) {
    console.error('Error deleting favorite:', error)
    showToastNotification('Failed to delete favorite. Please try again.', 'error')
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Helper function to get phonetics safely
function getPhonetics(favorite) {
  return favorite.phonetics || []
}

// Helper function to get meanings safely
function getMeanings(favorite) {
  return favorite.partOfSpeech || []
}

function showToastNotification(message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  // Auto-hide after 3 seconds
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

onMounted(() => {
  loadFavorites()
})
</script>

<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">My Favorites</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Your collection of saved words and personal notes
        </p>
      </div>

      <div class="max-w-4xl mx-auto">
        <div v-if="favorites.length > 0" class="space-y-6">
          <div
            v-for="favorite in favorites"
            :key="favorite.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200"
          >
            <div class="p-6">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-3">
                    <h2 class="text-2xl font-bold text-gray-900">{{ favorite.word }}</h2>
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                      Saved
                    </span>
                  </div>
                  
                  <div v-if="getPhonetics(favorite).length > 0" class="mb-3">
                    <template v-for="(phonetic, phoneticIndex) in getPhonetics(favorite)" :key="phoneticIndex">
                      <p class="text-gray-600 text-lg font-mono">
                        {{ phonetic.text }}
                      </p>
                    </template>
                  </div>
                  
                  <div v-if="favorite.note" class="mb-4">
                    <p class="text-gray-700 leading-relaxed">{{ favorite.note }}</p>
                  </div>
                  
                  <div class="text-sm text-gray-500">
                    <span>Saved on {{ formatDate(favorite.created_at) }}</span>
                    <span v-if="favorite.updated_at !== favorite.created_at" class="ml-2">
                      • Updated {{ formatDate(favorite.updated_at) }}
                    </span>
                  </div>
                </div>
                
                <div class="flex gap-2 ml-4">
                  <button 
                    @click="toggleCard(favorite.id)"
                    class="bg-gray-100 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-200 transition-colors duration-200 text-sm font-medium"
                  >
                    <svg 
                      class="w-4 h-4 inline mr-1 transition-transform duration-200" 
                      :class="{ 'rotate-180': isCardExpanded(favorite.id) }"
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    {{ isCardExpanded(favorite.id) ? 'Hide' : 'Show' }} Details
                  </button>
                  <button 
                    @click="openEditModal(favorite)"
                    class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md hover:bg-blue-200 transition-colors duration-200 text-sm font-medium"
                  >
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                  </button>
                  <button 
                    @click="openDeleteConfirmModal(favorite)"
                    class="bg-red-100 text-red-700 px-3 py-2 rounded-md hover:bg-red-200 transition-colors duration-200 text-sm font-medium"
                  >
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
            </div>

            <div 
              v-if="isCardExpanded(favorite.id)"
              class="border-t border-gray-200 bg-gray-50"
            >
              <div class="p-6">
                <div class="space-y-6">
                  <div
                    v-for="(partOfSpeech, posIndex) in favorite.partOfSpeech"
                    :key="posIndex"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                  >
                    <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                      <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ partOfSpeech }}</h3>
                    </div>

                    <div class="p-6">
                      <div class="space-y-6">
                        <div
                          v-for="(definition, defIndex) in favorite.definitions[posIndex]"
                          :key="defIndex"
                          class="relative"
                        >
                          <div class="flex items-center gap-3 mb-4">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                              {{ defIndex + 1 }}
                            </span>
                          </div>

                          <div class="mb-4">
                            <p class="text-gray-700 text-lg leading-relaxed">{{ definition }}</p>
                          </div>

                          <div v-if="favorite.examples[posIndex] && favorite.examples[posIndex][defIndex] && favorite.examples[posIndex][defIndex].trim()" class="mb-4">
                            <div class="bg-gray-50 rounded-md p-4 border-l-4 border-blue-500">
                              <p class="text-gray-700 italic">"{{ favorite.examples[posIndex][defIndex] }}"</p>
                            </div>
                          </div>

                          <div v-if="defIndex < favorite.definitions[posIndex].length - 1" class="border-b border-gray-100 mt-6 pb-6"></div>
                        </div>
                      </div>

                      <div v-if="favorite.synonyms[posIndex] && favorite.synonyms[posIndex].length > 0" class="mt-8 pt-6 border-t border-gray-100">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Synonyms</h4>
                        <div class="flex flex-wrap gap-2">
                          <span
                            v-for="(synonym, synonymIndex) in favorite.synonyms[posIndex]"
                            :key="synonymIndex"
                            class="bg-blue-50 text-blue-700 px-3 py-2 rounded-md text-sm font-medium"
                          >
                            {{ synonym }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="!isLoading" class="text-center py-12">
          <div class="max-w-md mx-auto">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No favorites yet</h3>
            <p class="text-gray-500">Start saving words to see them here</p>
          </div>
        </div>

        <div v-if="isLoading" class="text-center py-12">
          <div class="flex items-center justify-center">
            <svg class="animate-spin h-8 w-8 text-blue-600" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-3 text-gray-600">Loading favorites...</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showEditModal" class="fixed inset-0 backdrop-blur-[1px] flex items-center justify-center z-50 animate-fadeIn">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-modalSlideIn">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Edit "{{ currentFavorite?.word }}"</h3>
          <button 
            @click="closeEditModal"
            class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="p-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Note:</label>
          <textarea
            v-model="editNoteText"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
            placeholder="Add your notes about this word..."
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
          <button
            @click="closeEditModal"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors duration-200"
          >
            Cancel
          </button>
          <button
            @click="updateFavorite"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
          >
            Update
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDeleteConfirmModal" class="fixed inset-0 backdrop-blur-[1px] flex items-center justify-center z-50 animate-fadeIn">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-modalSlideIn">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Delete Favorite</h3>
          <button 
            @click="closeDeleteConfirmModal"
            class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="p-6">
          <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
              <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
            <div class="ml-3">
              <h4 class="text-lg font-medium text-gray-900">Are you sure?</h4>
              <p class="text-sm text-gray-500 mt-1">
                This will move "{{ favoriteToDelete?.word }}" to trash.
              </p>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
          <button
            @click="closeDeleteConfirmModal"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors duration-200"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200"
          >
            Move to Trash
          </button>
        </div>
      </div>
    </div>

    <div 
      v-if="showToast"
      class="fixed top-4 right-4 z-50 animate-toastSlideIn"
    >
      <div 
        :class="[
          'px-6 py-4 rounded-lg shadow-lg max-w-sm',
          toastType === 'success' ? 'bg-green-500 text-white' : '',
          toastType === 'error' ? 'bg-red-500 text-white' : '',
          toastType === 'info' ? 'bg-blue-500 text-white' : ''
        ]"
      >
        <div class="flex items-center">
          <svg v-if="toastType === 'success'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <svg v-if="toastType === 'error'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          <svg v-if="toastType === 'info'" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="font-medium">{{ toastMessage }}</span>
        </div>
      </div>
    </div>
  </Layout>
</template>

<style scoped>
/* Custom scrollbar for better UX */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Modal Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

.animate-modalSlideIn {
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes toastSlideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.animate-toastSlideIn {
  animation: toastSlideIn 0.3s ease-out;
}
</style>
