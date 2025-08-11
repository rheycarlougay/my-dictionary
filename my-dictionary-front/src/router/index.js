import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/auth/Login.vue'
import Register from '../views/auth/Register.vue'
import Dictionary from '../views/Dictionary.vue'
import Favorites from '../views/Favorites.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'Login', component: Login },
  { path: '/register', name: 'Register', component: Register },
  { 
    path: '/dictionary', 
    name: 'Dictionary', 
    component: Dictionary,
    meta: { requiresAuth: true }
  },
  { 
    path: '/favorites', 
    name: 'Favorites', 
    component: Favorites,
    meta: { requiresAuth: true }
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if ((to.name === 'Login' || to.name === 'Register') && token) {
    next('/dictionary')
  } else {
    next()
  }
})

export default router