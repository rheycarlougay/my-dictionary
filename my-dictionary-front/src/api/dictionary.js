import api from './axios'

const url = '/dictionary'
const favoritesUrl = '/favorites'

async function search(word) {
  try {
    const { data } = await api.get(`${url}/search`, {
      params: {
        q: word,
      },
    })
    return data;
  } catch (error) {
    console.error('API Error:', error.response || error)
    throw error;
  }
}

async function save(word, note) {
  try {
    const { data } = await api.post(`${favoritesUrl}`, {
      word: word,
      note: note,
    })
    return data;
  } catch (error) {
    console.error('API Error:', error.response || error)
    throw error;
  }
}

async function getFavorites() {
  try {
    const { data } = await api.get(`${favoritesUrl}`)
    return data;
  } catch (error) {
    console.error('API Error:', error.response || error)
    throw error;
  }
}

async function searchFavorites(word) {
  try {
    const { data } = await api.get(`${favoritesUrl}/search`, {
      params: {
        word: word,
      },
    })
    return data;
  } catch (error) {
    console.error('API Error:', error.response || error)
    throw error;
  }
}

async function updateFavorite(id, note) {
  try {
    const { data } = await api.put(`${favoritesUrl}/${id}`, {
      note: note,
    })
    return data;
  } catch (error) {
    console.error('API Error:', error.response || error)
    throw error;
  }
}

async function deleteFavorite(id) {
  try {
    const { data } = await api.delete(`${favoritesUrl}/${id}`)
    return data;
  } catch (error) {
    console.error('API Error:', error.response || error)
    throw error;
  }
}

export default { 
  search, 
  save, 
  getFavorites, 
  searchFavorites, 
  updateFavorite, 
  deleteFavorite,
}