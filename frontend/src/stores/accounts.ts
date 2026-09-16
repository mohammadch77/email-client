import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import client from '@/api/client'

export interface EmailAccount {
  id: number
  email: string
  display_name: string | null
  status: string
  last_synced_at: string | null
}

export interface Folder {
  id: number
  name: string
  type: string
  imap_name: string
  unread_count: number
  total_count: number
}

export const useAccountsStore = defineStore('accounts', () => {
  const accounts = ref<EmailAccount[]>([])
  const activeAccount = ref<EmailAccount | null>(null)
  const folders = ref<Folder[]>([])
  const loading = ref(false)

  async function fetchAccounts() {
    loading.value = true
    try {
      const res = await client.get('/email-accounts')
      accounts.value = res.data.data
      const first = accounts.value[0]
      if (first && !activeAccount.value) {
        await selectAccount(first)
      }
    } finally {
      loading.value = false
    }
  }

  async function selectAccount(account: EmailAccount) {
    activeAccount.value = account
    await fetchFolders(account.id)
  }

  async function fetchFolders(accountId: number) {
    const res = await client.get(`/email-accounts/${accountId}/folders`)
    folders.value = res.data.data
  }

  const inboxFolder = computed(() => folders.value.find((f) => f.type === 'inbox'))

  return {
    accounts,
    activeAccount,
    folders,
    loading,
    fetchAccounts,
    selectAccount,
    fetchFolders,
    inboxFolder,
  }
})
