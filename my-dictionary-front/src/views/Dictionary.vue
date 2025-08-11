<script setup>
import { ref, onMounted } from 'vue'
import homeApi from '../api/dictionary.js'
import Layout from '../components/Layout.vue'

const word = ref('')
const searchedWords = ref([])
const isLoading = ref(false)
const currentWord = ref('')
const showModal = ref(false)
const noteText = ref('')

const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

const savedFavorites = ref(new Set())

onMounted(async () => {
  await loadUserFavorites()
})

async function loadUserFavorites() {
  try {
    const response = await homeApi.getFavorites()
    if (response.data && Array.isArray(response.data)) {
      const favoriteWords = response.data.map(favorite => favorite.word.toLowerCase())
      savedFavorites.value = new Set(favoriteWords)
    }
  } catch (error) {
    console.error('Error loading favorites:', error)
  }
}

function isWordSaved(word) {
  return savedFavorites.value.has(word.toLowerCase())
}

async function search() {
  if (!word.value.trim()) return
  
  isLoading.value = true
  try {
    const response = await homeApi.search(word.value)
    
    if (response.status_code != 200) {
      searchedWords.value = []
      return
    }

    if (response.data && Array.isArray(response.data)) {
      searchedWords.value = response.data.flat()
    } else {
      searchedWords.value = response.data || []
    }
  } catch (error) {
    console.error('Error fetching words:', error)
    searchedWords.value = []
  } finally {
    isLoading.value = false
  }
}

function openSaveModal(word) {
  currentWord.value = word
  showModal.value = true
  noteText.value = ''
}

function closeModal() {
  currentWord.value = ''
  showModal.value = false
  noteText.value = ''
}

async function save() {
  try {
    const response = await homeApi.save(currentWord.value, noteText.value)
    console.log('Saving note for word:', currentWord.value, 'Note:', noteText.value)
    
    savedFavorites.value.add(currentWord.value.toLowerCase())
    
    closeModal()
    showToastNotification('Word saved successfully!', 'success')
  } catch (error) {
    console.error('Error saving word:', error)
    showToastNotification('Failed to save word. Please try again.', 'error')
  }
}

function showToastNotification(message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}
</script>

<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Dictionary Explorer</h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Discover the meaning, pronunciation, and usage of words from around the world
        </p>
      </div>

      <div class="max-w-2xl mx-auto mb-12">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex gap-4">
            <input
              v-model="word"
              @keyup.enter="search"
              type="text"
              placeholder="Search for a word..."
              class="flex-1 px-4 py-3 text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
            />
            <button
              @click="search"
              :disabled="isLoading"
              class="px-6 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading" class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Searching...
              </span>
              <span v-else>Search</span>
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-4xl mx-auto">
        <div v-if="searchedWords.length > 0" class="space-y-8">
          <div
            v-for="(item, index) in searchedWords"
            :key="index"
            class="relative"
          >
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ item.word }}</h2>
                   <div class="mb-1">
                     <template v-for="(phonetic, phoneticIndex) in item.phonetics" :key="phoneticIndex">
                       <p class="text-gray-600 text-xl font-mono">
                         {{ phonetic }}
                       </p>
                     </template>
                   </div>
                </div>
                
                 <div class="flex gap-2">
                   <button 
                     @click="openSaveModal(item.word)"
                     :disabled="isWordSaved(item.word)"
                     :class="[
                       'px-4 py-2 rounded-md transition-colors duration-200 text-sm font-medium',
                       isWordSaved(item.word) 
                         ? 'bg-yellow-100 text-yellow-700 cursor-not-allowed opacity-75' 
                         : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                     ]"
                   >
                     <svg 
                       class="w-4 h-4 inline mr-2" 
                       :class="isWordSaved(item.word) ? 'fill-yellow-400' : 'fill-none'"
                       stroke="currentColor" 
                       viewBox="0 0 24 24"
                     >
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                     </svg>
                     {{ isWordSaved(item.word) ? 'Saved' : 'Save' }}
                   </button>
                 </div>
              </div>
            </div>

             <div class="space-y-6">
               <div
                 v-for="(partOfSpeech, posIndex) in item.partOfSpeech"
                 :key="posIndex"
                 class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
               >
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                  <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ partOfSpeech }}</h3>
                </div>

                <div class="p-6">
                  <div class="space-y-6">
                                         <div
                       v-for="(definition, defIndex) in item.definitions[posIndex]"
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

                       <div v-if="item.examples[posIndex] && item.examples[posIndex][defIndex] && item.examples[posIndex][defIndex].trim()" class="mb-4">
                         <div class="bg-gray-50 rounded-md p-4 border-l-4 border-blue-500">
                           <p class="text-gray-700 italic">"{{ item.examples[posIndex][defIndex] }}"</p>
                         </div>
                       </div>

                       <div v-if="defIndex < item.definitions[posIndex].length - 1" class="border-b border-gray-100 mt-6 pb-6"></div>
                    </div>
                  </div>

                   <div v-if="item.synonyms[posIndex] && item.synonyms[posIndex].length > 0" class="mt-8 pt-6 border-t border-gray-100">
                     <h4 class="text-lg font-semibold text-gray-900 mb-4">Synonyms</h4>
                     <div class="flex flex-wrap gap-2">
                       <span
                         v-for="(synonym, synonymIndex) in item.synonyms[posIndex]"
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

            <div v-if="index < searchedWords.length - 1" class="border-b-2 border-gray-300 mt-8 pb-8"></div>
          </div>
        </div>

        <div v-else-if="!isLoading" class="text-center py-12">
          <div class="max-w-md mx-auto">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No words found</h3>
            <p class="text-gray-500">Try searching for a different word or browse our collection</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 backdrop-blur-[1px] flex items-center justify-center z-50 animate-fadeIn">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-modalSlideIn">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Save "{{ currentWord }}"</h3>
          <button 
            @click="closeModal"
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
            v-model="noteText"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
            placeholder="Add your notes about this word..."
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
          <button
            @click="closeModal"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors duration-200"
          >
            Cancel
          </button>
          <button
            @click="save"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
          >
            Save
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
