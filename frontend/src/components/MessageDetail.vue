<script setup lang="ts">
import { useMessagesStore } from '@/stores/messages'
import { useComposeStore } from '@/stores/compose'

const messages = useMessagesStore()
const compose = useComposeStore()

function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString()
}
</script>

<template>
  <div class="flex h-full flex-col">
    <div v-if="messages.loadingDetail" class="flex h-full items-center justify-center">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-gray-600 border-t-white" />
    </div>

    <div v-else-if="messages.selectedMessage" class="flex h-full flex-col">
      <div class="border-b border-gray-800 p-4">
        <h2 class="text-lg font-semibold text-white">
          {{ messages.selectedMessage.subject || '(no subject)' }}
        </h2>
        <div class="mt-2 flex items-center justify-between text-sm text-gray-400">
          <div>
            <span class="text-white">{{
              messages.selectedMessage.from_name || messages.selectedMessage.from_email
            }}</span>
            <span class="ml-1 text-gray-500">&lt;{{ messages.selectedMessage.from_email }}&gt;</span>
          </div>
          <span>{{ formatDate(messages.selectedMessage.received_at) }}</span>
        </div>
        <div v-if="messages.selectedMessage.recipients?.length" class="mt-1 text-xs text-gray-500">
          To:
          {{
            messages.selectedMessage.recipients
              .filter((r) => r.type === 'to')
              .map((r) => r.name || r.email)
              .join(', ')
          }}
        </div>

        <div class="mt-3 flex gap-2">
          <button
            class="rounded bg-gray-800 px-3 py-1 text-sm text-white hover:bg-gray-700"
            @click="
              compose.openReply(
                messages.selectedMessage!.imap_uid,
                messages.selectedMessage!.from_email,
                messages.selectedMessage!.subject ?? '',
              )
            "
          >
            Reply
          </button>
          <button
            class="rounded bg-gray-800 px-3 py-1 text-sm text-white hover:bg-gray-700"
            @click="
              compose.openReplyAll(
                messages.selectedMessage!.imap_uid,
                messages.selectedMessage!.from_email,
                messages
                  .selectedMessage!.recipients?.filter((r) => r.type === 'cc')
                  .map((r) => r.email)
                  .join(', ') ?? '',
                messages.selectedMessage!.subject ?? '',
              )
            "
          >
            Reply All
          </button>
          <button
            class="rounded bg-gray-800 px-3 py-1 text-sm text-white hover:bg-gray-700"
            @click="
              compose.openForward(messages.selectedMessage!.imap_uid, messages.selectedMessage!.subject ?? '')
            "
          >
            Forward
          </button>
          <button class="rounded bg-red-900/50 px-3 py-1 text-sm text-red-300 hover:bg-red-900">
            Delete
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto p-4">
        <iframe
          v-if="messages.selectedMessage.body_html"
          :srcdoc="messages.selectedMessage.body_html"
          class="min-h-64 w-full border-0"
          sandbox="allow-same-origin"
        />
        <pre v-else class="whitespace-pre-wrap font-sans text-sm text-gray-300">{{
          messages.selectedMessage.body_text
        }}</pre>

        <div v-if="messages.selectedMessage.attachments?.length" class="mt-4 border-t border-gray-800 pt-3">
          <div class="mb-2 text-xs uppercase text-gray-500">Attachments</div>
          <div class="space-y-1">
            <div
              v-for="att in messages.selectedMessage.attachments"
              :key="att.id"
              class="flex items-center justify-between rounded bg-gray-800/50 px-3 py-2 text-sm text-gray-300"
            >
              <span class="truncate">{{ att.filename }}</span>
              <span class="shrink-0 text-xs text-gray-500">{{ formatSize(att.size) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
