<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAccountsStore } from '@/stores/accounts'
import { useMessagesStore } from '@/stores/messages'
import SidebarNav from '@/components/SidebarNav.vue'
import TopBar from '@/components/TopBar.vue'
import ComposeModal from '@/components/ComposeModal.vue'
import client from '@/api/client'

const route = useRoute()
const auth = useAuthStore()
const accounts = useAccountsStore()
const messages = useMessagesStore()

let syncInterval: ReturnType<typeof setInterval> | null = null

async function triggerSync() {
  const accountId = accounts.activeAccount?.id
  if (!accountId) return
  try {
    await client.post(`/email-accounts/${accountId}/sync`)
    await accounts.fetchFolders(accountId)
    const folderId = Number(route.query.folder_id) || accounts.inboxFolder?.id || null
    if (folderId) {
      await messages.fetchMessages(accountId, folderId)
    }
  } catch (e) {
    // silent fail
  }
}

onMounted(async () => {
  await auth.fetchMe()
  await accounts.fetchAccounts()
  // Sync immediately on load
  await triggerSync()
  // Then every 2 minutes
  syncInterval = setInterval(triggerSync, 2 * 60 * 1000)
})

onUnmounted(() => {
  if (syncInterval) clearInterval(syncInterval)
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
    <ComposeModal />
  </div>
</template>
