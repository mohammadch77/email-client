<script setup lang="ts">
import { onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAccountsStore } from '@/stores/accounts'
import { useMessagesStore } from '@/stores/messages'
import MessageList from '@/components/MessageList.vue'
import MessageDetail from '@/components/MessageDetail.vue'

const route = useRoute()
const accounts = useAccountsStore()
const messages = useMessagesStore()

const folderId = computed(() => Number(route.query.folder_id) || accounts.inboxFolder?.id || null)
const accountId = computed(
  () => Number(route.query.account_id) || accounts.activeAccount?.id || null,
)

async function loadMessages(page = 1) {
  if (!accountId.value || !folderId.value) return
  messages.clearSelected()
  await messages.fetchMessages(accountId.value, folderId.value, page)
}

function goToPage(page: number) {
  if (page < 1 || page > messages.lastPage) return
  loadMessages(page)
}

watch([folderId, accountId], () => loadMessages())
onMounted(() => loadMessages())
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="flex flex-1 overflow-hidden">
      <div
        :class="messages.selectedMessage ? 'w-2/5' : 'w-full'"
        class="flex flex-col overflow-y-auto border-r border-gray-800"
      >
        <div
          v-if="messages.searchQuery"
          class="px-4 py-2 bg-blue-900/30 border-b border-blue-800 text-sm text-blue-300 flex items-center justify-between shrink-0"
        >
          <span>
            Search results for "{{ messages.searchQuery }}" ({{ messages.searchResults.length }} found)
          </span>
          <button @click="messages.clearSearch()" class="text-blue-400 hover:text-white">
            Clear search
          </button>
        </div>

        <MessageList
          class="flex-1"
          :account-id="accountId"
          :folder-id="folderId"
          :show-search="!!messages.searchQuery"
        />

        <div
          v-if="!messages.loading && !messages.searchQuery && messages.messages.length > 0"
          class="flex items-center justify-between border-t border-gray-800 px-4 py-2 text-sm text-gray-400"
        >
          <span>Page {{ messages.currentPage }} of {{ messages.lastPage }}</span>
          <div class="flex gap-2">
            <button
              class="rounded px-2 py-1 hover:bg-gray-800 disabled:opacity-30"
              :disabled="messages.currentPage <= 1"
              @click="goToPage(messages.currentPage - 1)"
            >
              Prev
            </button>
            <button
              class="rounded px-2 py-1 hover:bg-gray-800 disabled:opacity-30"
              :disabled="messages.currentPage >= messages.lastPage"
              @click="goToPage(messages.currentPage + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <MessageDetail v-if="messages.selectedMessage" class="flex-1 overflow-y-auto" />
    </div>
  </div>
</template>
