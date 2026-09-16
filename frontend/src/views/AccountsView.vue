<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAccountsStore } from '@/stores/accounts'
import client from '@/api/client'

const accounts = useAccountsStore()

const showForm = ref(false)
const saving = ref(false)
const testing = ref(false)
const formError = ref('')
const formSuccess = ref('')

const form = ref({
  email: '',
  display_name: '',
  username: '',
  password: '',
  imap_host: 'mail.iranserver.com',
  imap_port: 993,
  imap_encryption: 'ssl',
  smtp_host: 'mail.iranserver.com',
  smtp_port: 465,
  smtp_encryption: 'ssl',
})

function resetForm() {
  form.value = {
    email: '',
    display_name: '',
    username: '',
    password: '',
    imap_host: 'mail.iranserver.com',
    imap_port: 993,
    imap_encryption: 'ssl',
    smtp_host: 'mail.iranserver.com',
    smtp_port: 465,
    smtp_encryption: 'ssl',
  }
  formError.value = ''
  formSuccess.value = ''
}

async function testConnection() {
  if (!form.value.email || !form.value.password) {
    formError.value = 'Enter email and password first'
    return
  }
  testing.value = true
  formError.value = ''
  try {
    const res = await client.post('/email-accounts', form.value)
    const id = res.data.data.id
    await client.post(`/email-accounts/${id}/test-connection`)
    formSuccess.value = 'Connection successful!'
    await accounts.fetchAccounts()
    showForm.value = false
    resetForm()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Connection failed'
  } finally {
    testing.value = false
  }
}

async function saveAccount() {
  saving.value = true
  formError.value = ''
  try {
    await client.post('/email-accounts', form.value)
    await accounts.fetchAccounts()
    showForm.value = false
    resetForm()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? 'Failed to save account'
  } finally {
    saving.value = false
  }
}

async function deleteAccount(id: number) {
  if (!confirm('Delete this account? All synced messages will be removed.')) return
  try {
    await client.delete(`/email-accounts/${id}`)
    await accounts.fetchAccounts()
  } catch {
    alert('Failed to delete account')
  }
}

async function syncAccount(id: number) {
  try {
    await client.post(`/email-accounts/${id}/sync`)
    alert('Sync started in background')
  } catch {
    alert('Failed to trigger sync')
  }
}

onMounted(() => accounts.fetchAccounts())
</script>

<template>
  <div class="p-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-white">Email Accounts</h1>
      <button
        @click="showForm = !showForm"
        class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded-lg transition"
      >
        + Add Account
      </button>
    </div>

    <!-- Add account form -->
    <div v-if="showForm" class="bg-gray-900 rounded-xl p-6 mb-6 border border-gray-800">
      <h2 class="text-lg font-medium text-white mb-4">Connect IranServer Account</h2>

      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="block text-xs text-gray-400 mb-1">Email Address</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="you@example.com"
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500"
          />
        </div>

        <div class="col-span-2">
          <label class="block text-xs text-gray-400 mb-1">Display Name (optional)</label>
          <input
            v-model="form.display_name"
            type="text"
            placeholder="My Work Email"
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500"
          />
        </div>

        <div>
          <label class="block text-xs text-gray-400 mb-1">Username (usually same as email)</label>
          <input
            v-model="form.username"
            type="text"
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500"
          />
        </div>

        <div>
          <label class="block text-xs text-gray-400 mb-1">Mailbox Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-blue-500"
          />
        </div>

        <div class="col-span-2">
          <details class="text-sm text-gray-400">
            <summary class="cursor-pointer hover:text-gray-200 mb-3">Advanced IMAP/SMTP Settings</summary>
            <div class="grid grid-cols-3 gap-3 mt-2">
              <input
                v-model="form.imap_host"
                placeholder="IMAP Host"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-xs text-white focus:outline-none"
              />
              <input
                v-model.number="form.imap_port"
                type="number"
                placeholder="993"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-xs text-white focus:outline-none"
              />
              <select
                v-model="form.imap_encryption"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-xs text-white focus:outline-none"
              >
                <option value="ssl">SSL</option>
                <option value="tls">TLS</option>
                <option value="none">None</option>
              </select>
              <input
                v-model="form.smtp_host"
                placeholder="SMTP Host"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-xs text-white focus:outline-none"
              />
              <input
                v-model.number="form.smtp_port"
                type="number"
                placeholder="465"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-xs text-white focus:outline-none"
              />
              <select
                v-model="form.smtp_encryption"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1.5 text-xs text-white focus:outline-none"
              >
                <option value="ssl">SSL</option>
                <option value="tls">TLS</option>
                <option value="none">None</option>
              </select>
            </div>
          </details>
        </div>
      </div>

      <p v-if="formError" class="text-red-400 text-sm mt-3">{{ formError }}</p>
      <p v-if="formSuccess" class="text-green-400 text-sm mt-3">{{ formSuccess }}</p>

      <div class="flex gap-3 mt-5">
        <button
          @click="testConnection"
          :disabled="testing"
          class="bg-green-700 hover:bg-green-600 text-white text-sm px-4 py-2 rounded-lg transition disabled:opacity-50"
        >
          {{ testing ? 'Testing...' : 'Test & Save' }}
        </button>
        <button
          @click="saveAccount"
          :disabled="saving"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded-lg transition disabled:opacity-50"
        >
          {{ saving ? 'Saving...' : 'Save Without Test' }}
        </button>
        <button
          @click="showForm = false; resetForm()"
          class="text-gray-400 hover:text-white text-sm px-4 py-2 transition"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Account list -->
    <div v-if="accounts.loading" class="text-gray-500 text-sm">Loading accounts...</div>
    <div v-else-if="!accounts.accounts.length" class="text-center py-12 text-gray-500">
      No accounts connected yet.
    </div>
    <ul v-else class="space-y-3">
      <li
        v-for="acc in accounts.accounts"
        :key="acc.id"
        class="bg-gray-900 rounded-xl p-4 border border-gray-800 flex items-center justify-between"
      >
        <div>
          <p class="text-white font-medium">{{ acc.email }}</p>
          <p class="text-xs text-gray-400 mt-0.5">
            {{ acc.display_name ?? 'No display name' }}
            ·
            <span :class="acc.status === 'active' ? 'text-green-400' : 'text-red-400'">
              {{ acc.status }}
            </span>
          </p>
          <p class="text-xs text-gray-600 mt-0.5">
            Last sync: {{ acc.last_synced_at ? new Date(acc.last_synced_at).toLocaleString() : 'Never' }}
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="syncAccount(acc.id)"
            class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg transition"
          >
            Sync
          </button>
          <button
            @click="deleteAccount(acc.id)"
            class="text-xs bg-red-900 hover:bg-red-800 text-red-300 px-3 py-1.5 rounded-lg transition"
          >
            Delete
          </button>
        </div>
      </li>
    </ul>
  </div>
</template>
