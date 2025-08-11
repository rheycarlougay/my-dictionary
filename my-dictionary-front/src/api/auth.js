import api from './axios.js'

export async function login(credentials) {
  const response = await api.post('/auth/login', credentials)
  return response.data
}

export async function register(userData) {
  const response = await api.post('/auth/register', userData)
  return response.data
}

export async function logout() {
  const response = await api.post('/auth/logout')
  return response.data
}

export async function check() {
  const response = await api.get('/auth/check')
  return response.data
}

export async function profile() {
  const response = await api.get('/auth/profile')
  return response.data
}

export default {
  login,
  register,
  logout,
  check,
  profile
}
