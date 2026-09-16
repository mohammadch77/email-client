<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  loading.value = true
  try {
    await auth.register(
      name.value,
      email.value,
      password.value,
      passwordConfirmation.value,
    )
    router.push('/inbox')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="bg-gray-950 min-h-screen flex items-center justify-center px-4">
    <div class="bg-gray-900 rounded-xl shadow-lg p-8 w-full max-w-sm">
      <h1 class="text-2xl font-semibold text-white text-center mb-8">UniversalMail</h1>

      <form class="space-y-4" @submit.prevent="handleRegister">
        <div>
          <label for="name" class="block text-sm text-gray-400 mb-1">Name</label>
          <input
            id="name"
            v-model="name"
            type="text"
            required
            autocomplete="name"
            class="w-full rounded-lg bg-gray-800 border border-gray-700 text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <div>
          <label for="email" class="block text-sm text-gray-400 mb-1">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg bg-gray-800 border border-gray-700 text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <div>
          <label for="password" class="block text-sm text-gray-400 mb-1">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
            class="w-full rounded-lg bg-gray-800 border border-gray-700 text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm text-gray-400 mb-1">Confirm Password</label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            required
            autocomplete="new-password"
            class="w-full rounded-lg bg-gray-800 border border-gray-700 text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          />
        </div>

        <div v-if="error" class="rounded-lg bg-red-950 border border-red-800 text-red-300 text-sm px-3 py-2">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-blue-600 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-2 text-sm transition-colors"
        >
          {{ loading ? 'Creating account...' : 'Create account' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-400 mt-6">
        Already have an account?
        <RouterLink to="/login" class="text-blue-500 hover:text-blue-400">Sign in</RouterLink>
      </p>
    </div>
  </div>
</template>
