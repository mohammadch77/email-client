<script setup lang="ts">
import { useMessagesStore } from '@/stores/messages'

const props = defineProps<{
  accountId: number | null
  folderId: number | null
}>()

const messages = useMessagesStore()

function formatDate(dateStr: string | null): string {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

async function selectMessage(uid: string) {
  if (!props.accountId) return
  await Promise.all([
    messages.fetchMessage(props.accountId, uid),
    messages.markRead(props.accountId, uid),
  ])
}

async function toggleStar(uid: string) {
  if (!props.accountId) return
  await messages.toggleStar(props.accountId, uid)
}
</script>

<template>
  <div>
    <div v-if="messages.loading" class="p-3 space-y-3">
      <div v-for="i in 3" :key="i" class="h-12 rounded bg-gray-800 animate-pulse" />
    </div>

    <div
      v-else-if="messages.messages.length === 0"
      class="flex h-full items-center justify-center text-gray-500 text-sm"
    >
      No messages
    </div>

    <ul v-else class="divide-y divide-gray-800">
      <li
        v-for="msg in messages.messages"
        :key="msg.id"
        @click="selectMessage(msg.imap_uid)"
        class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-800/60"
        :class="msg.is_read ? 'text-gray-400' : 'bg-gray-800/30 text-white'"
      >
        <button
          @click.stop="toggleStar(msg.imap_uid)"
          class="shrink-0 text-lg leading-none"
          :class="msg.is_starred ? 'text-yellow-400' : 'text-gray-600 hover:text-gray-400'"
        >
          ★
        </button>

        <div class="min-w-0 flex-1">
          <div class="flex items-center justify-between gap-2">
            <span class="truncate" :class="!msg.is_read && 'font-semibold'">
              {{ msg.from_name || msg.from_email }}
            </span>
            <span class="shrink-0 text-xs text-gray-500">{{ formatDate(msg.received_at) }}</span>
          </div>
          <div class="flex items-center gap-1 truncate text-sm">
            <span class="truncate">{{ msg.subject || '(no subject)' }}</span>
            <span v-if="msg.has_attachments" class="shrink-0 text-gray-500">📎</span>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
