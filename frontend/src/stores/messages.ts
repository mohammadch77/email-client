import { defineStore } from 'pinia'
import { ref } from 'vue'
import client from '@/api/client'

export interface Recipient {
  email: string
  name: string | null
}

export interface Attachment {
  id: number
  filename: string
  size: number
  content_type: string
}

export interface Message {
  id: number
  imap_uid: string
  subject: string | null
  from_email: string
  from_name: string | null
  is_read: boolean
  is_starred: boolean
  has_attachments: boolean
  received_at: string | null
  status: string
  folder_id: number
}

export interface MessageDetail extends Message {
  body_html: string | null
  body_text: string | null
  recipients: (Recipient & { type: string })[]
  attachments?: Attachment[]
}

export const useMessagesStore = defineStore('messages', () => {
  const messages = ref<Message[]>([])
  const selectedMessage = ref<MessageDetail | null>(null)
  const loading = ref(false)
  const loadingDetail = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)

  async function fetchMessages(accountId: number, folderId: number, page = 1) {
    loading.value = true
    try {
      const res = await client.get(`/email-accounts/${accountId}/messages`, {
        params: { folder_id: folderId, page, limit: 50 },
      })
      messages.value = res.data.data
      currentPage.value = res.data.meta?.current_page ?? page
      lastPage.value = res.data.meta?.last_page ?? 1
      total.value = res.data.meta?.total ?? res.data.data.length
    } finally {
      loading.value = false
    }
  }

  async function fetchMessage(accountId: number, uid: string) {
    loadingDetail.value = true
    try {
      const res = await client.get(`/email-accounts/${accountId}/messages/${uid}`, {
        params: {
          folder_id: selectedMessage.value?.folder_id ?? messages.value[0]?.folder_id,
        },
      })
      selectedMessage.value = res.data.data
    } finally {
      loadingDetail.value = false
    }
  }

  async function markRead(accountId: number, uid: string) {
    await client.post(`/email-accounts/${accountId}/messages/${uid}/read`)
    const msg = messages.value.find((m) => m.imap_uid === uid)
    if (msg) msg.is_read = true
    if (selectedMessage.value?.imap_uid === uid) selectedMessage.value.is_read = true
  }

  async function markUnread(accountId: number, uid: string) {
    await client.post(`/email-accounts/${accountId}/messages/${uid}/unread`)
    const msg = messages.value.find((m) => m.imap_uid === uid)
    if (msg) msg.is_read = false
    if (selectedMessage.value?.imap_uid === uid) selectedMessage.value.is_read = false
  }

  async function toggleStar(accountId: number, uid: string) {
    const msg = messages.value.find((m) => m.imap_uid === uid)
    if (!msg) return
    if (msg.is_starred) {
      await client.post(`/email-accounts/${accountId}/messages/${uid}/unstar`)
      msg.is_starred = false
    } else {
      await client.post(`/email-accounts/${accountId}/messages/${uid}/star`)
      msg.is_starred = true
    }
    if (selectedMessage.value?.imap_uid === uid) selectedMessage.value.is_starred = msg.is_starred
  }

  function clearSelected() {
    selectedMessage.value = null
  }

  return {
    messages,
    selectedMessage,
    loading,
    loadingDetail,
    currentPage,
    lastPage,
    total,
    fetchMessages,
    fetchMessage,
    markRead,
    markUnread,
    toggleStar,
    clearSelected,
  }
})
