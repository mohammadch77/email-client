<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAccountsStore } from '@/stores/accounts'
import SidebarNav from '@/components/SidebarNav.vue'
import TopBar from '@/components/TopBar.vue'

const auth = useAuthStore()
const accounts = useAccountsStore()

onMounted(async () => {
  await auth.fetchMe()
  await accounts.fetchAccounts()
})
</script>

<template>
  <div class="flex flex-col h-screen bg-gray-950 text-gray-100">
    <TopBar />
    <div class="flex flex-1 overflow-hidden">
      <SidebarNav />
      <main class="flex-1 overflow-y-auto bg-gray-900">
        <RouterView />
      </main>
    </div>
  </div>
</template>
