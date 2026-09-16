import { defineStore } from 'pinia'
import { ref } from 'vue'
import client from '@/api/client'
import { useAccountsStore } from '@/stores/accounts'

export type ComposeMode = 'new' | 'reply' | 'reply-all' | 'forward'

export interface ComposeState {
  mode: ComposeMode
  replyToUid?: string
  to: string
  cc: string
  subject: string
  body: string
}

export const useComposeStore = defineStore('compose', () => {
  const isOpen = ref(false)
  const sending = ref(false)
  const error = ref('')
  const state = ref<ComposeState>({
    mode: 'new',
    to: '',
    cc: '',
    subject: '',
    body: '',
  })

  function openNew() {
    state.value = { mode: 'new', to: '', cc: '', subject: '', body: '' }
    isOpen.value = true
    error.value = ''
  }

  function openReply(uid: string, toEmail: string, subject: string) {
    state.value = {
      mode: 'reply',
      replyToUid: uid,
      to: toEmail,
      cc: '',
      subject: subject.startsWith('Re:') ? subject : `Re: ${subject}`,
      body: '',
    }
    isOpen.value = true
    error.value = ''
  }

  function openReplyAll(uid: string, toEmail: string, cc: string, subject: string) {
    state.value = {
      mode: 'reply-all',
      replyToUid: uid,
      to: toEmail,
      cc,
      subject: subject.startsWith('Re:') ? subject : `Re: ${subject}`,
      body: '',
    }
    isOpen.value = true
    error.value = ''
  }

  function openForward(uid: string, subject: string) {
    state.value = {
      mode: 'forward',
      replyToUid: uid,
      to: '',
      cc: '',
      subject: subject.startsWith('Fwd:') ? subject : `Fwd: ${subject}`,
      body: '',
    }
    isOpen.value = true
    error.value = ''
  }

  function close() {
    isOpen.value = false
  }

  async function send() {
    const accounts = useAccountsStore()
    const accountId = accounts.activeAccount?.id
    if (!accountId) {
      error.value = 'No active account selected'
      return
    }

    sending.value = true
    error.value = ''

    try {
      const toList = state.value.to
        .split(',')
        .map((e) => e.trim())
        .filter(Boolean)
        .map((email) => ({ email, name: '' }))

      const ccList = state.value.cc
        .split(',')
        .map((e) => e.trim())
        .filter(Boolean)
        .map((email) => ({ email, name: '' }))

      const payload = {
        to: toList,
        cc: ccList.length ? ccList : undefined,
        subject: state.value.subject,
        body_html: `<p>${state.value.body.replace(/\n/g, '<br>')}</p>`,
        body_text: state.value.body,
      }

      const uid = state.value.replyToUid

      if (state.value.mode === 'reply' && uid) {
        await client.post(`/email-accounts/${accountId}/messages/${uid}/reply`, payload)
      } else if (state.value.mode === 'reply-all' && uid) {
        await client.post(`/email-accounts/${accountId}/messages/${uid}/reply-all`, payload)
      } else if (state.value.mode === 'forward' && uid) {
        await client.post(`/email-accounts/${accountId}/messages/${uid}/forward`, payload)
      } else {
        await client.post(`/email-accounts/${accountId}/messages/send`, payload)
      }

      close()
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Send failed'
    } finally {
      sending.value = false
    }
  }

  return {
    isOpen,
    sending,
    error,
    state,
    openNew,
    openReply,
    openReplyAll,
    openForward,
    close,
    send,
  }
})
