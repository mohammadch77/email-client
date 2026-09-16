<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAccountsStore } from '@/stores/accounts'
import { useMessagesStore } from '@/stores/messages'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const accounts = useAccountsStore()
const messagesStore = useMessagesStore()
const router = useRouter()
const searchInput = ref('')

async function logout() {
  await auth.logout()
  router.push('/login')
}

async function doSearch() {
  const accountId = accounts.activeAccount?.id
  if (!accountId || !searchInput.value.trim()) return
  await messagesStore.search(accountId, searchInput.value)
}

function clearSearch() {
  searchInput.value = ''
  messagesStore.clearSearch()
}
</script>

<template>
  <header
    class="h-14 bg-gray-900 border-b border-gray-800 flex items-center justify-between px-4 shrink-0"
  >
    <span class="text-lg font-semibold text-white">WebMail</span>

    <!-- Search -->
    <div class="flex items-center gap-2 flex-1 max-w-md mx-4">
      <input
        v-model="searchInput"
        @keyup.enter="doSearch"
        placeholder="Search mail..."
        class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-white focus:outline-none focus:border-blue-500 placeholder-gray-500"
      />
      <button v-if="searchInput" @click="clearSearch" class="text-gray-400 hover:text-white text-sm">
        ✕
      </button>
      <button @click="doSearch" class="text-gray-400 hover:text-white text-sm px-2">🔍</button>
    </div>

    <!-- Account switcher -->
    <div class="flex items-center gap-2">
      <select
        v-if="accounts.accounts.length"
        :value="accounts.activeAccount?.id"
        @change="
          (e) =>
            accounts.selectAccount(
              accounts.accounts.find((a) => a.id === Number((e.target as HTMLSelectElement).value))!,
            )
        "
        class="bg-gray-800 text-gray-200 text-sm rounded px-2 py-1 border border-gray-700 focus:outline-none"
      >
        <option v-for="acc in accounts.accounts" :key="acc.id" :value="acc.id">
          {{ acc.email }}
        </option>
      </select>
      <span v-else class="text-gray-400 text-sm">No accounts</span>
    </div>

    <!-- Logout -->
    <button @click="logout" class="text-sm text-gray-400 hover:text-white transition">
      Logout
    </button>
  </header>
</template>
