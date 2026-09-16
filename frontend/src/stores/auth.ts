import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import client from '@/api/client'

interface User {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    const res = await client.post('/auth/login', { email, password })
    token.value = res.data.data.token
    user.value = res.data.data.user
    localStorage.setItem('auth_token', token.value as string)
  }

  async function register(
    name: string,
    email: string,
    password: string,
    password_confirmation: string,
  ) {
    const res = await client.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation,
    })
    token.value = res.data.data.token
    user.value = res.data.data.user
    localStorage.setItem('auth_token', token.value as string)
  }

  async function logout() {
    try {
      await client.post('/auth/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchMe() {
    const res = await client.get('/auth/me')
    user.value = res.data.data.user
  }

  return { user, token, isAuthenticated, login, register, logout, fetchMe }
})
