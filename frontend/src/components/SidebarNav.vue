<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAccountsStore, type Folder } from '@/stores/accounts'
import { useComposeStore } from '@/stores/compose'

const accounts = useAccountsStore()
const compose = useComposeStore()
const router = useRouter()
const route = useRoute()

function selectFolder(folder: Folder) {
  router.push({
    path: '/inbox',
    query: {
      folder_id: folder.id,
      account_id: accounts.activeAccount?.id,
    },
  })
}

const folderOrder = ['inbox', 'starred', 'sent', 'drafts', 'archive', 'spam', 'trash', 'custom']

const folderIcons: Record<string, string> = {
  inbox: '📥',
  starred: '⭐',
  sent: '📤',
  drafts: '📝',
  archive: '🗄️',
  spam: '🚫',
  trash: '🗑️',
  custom: '📁',
}

const sortedFolders = computed(() =>
  [...accounts.folders].sort((a, b) => {
    const ai = folderOrder.indexOf(a.type)
    const bi = folderOrder.indexOf(b.type)
    return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi)
  }),
)
</script>

<template>
  <nav class="w-64 bg-gray-950 border-r border-gray-800 flex flex-col overflow-y-auto shrink-0">
    <!-- Compose button -->
    <div class="p-3">
      <button
        @click="compose.openNew()"
        class="w-full bg-blue-600 hover:bg-blue-500 text-white rounded-lg py-2 text-sm font-medium transition"
      >
        + Compose
      </button>
    </div>

    <!-- Folder list -->
    <ul class="flex-1 px-2">
      <li v-for="folder in sortedFolders" :key="folder.id">
        <button
          @click="selectFolder(folder)"
          :class="[
            'flex items-center gap-2 px-3 py-2 rounded-lg',
            'text-sm hover:bg-gray-800 transition w-full mb-0.5',
            Number(route.query.folder_id) === folder.id
              ? 'bg-gray-800 text-white'
              : 'text-gray-300',
          ]"
        >
          <span>{{ folderIcons[folder.type] ?? '📁' }}</span>
          <span class="flex-1 truncate text-left">{{ folder.name }}</span>
          <span
            v-if="folder.unread_count > 0"
            class="bg-blue-600 text-white text-xs rounded-full px-1.5 py-0.5 min-w-[1.25rem] text-center"
          >
            {{ folder.unread_count }}
          </span>
        </button>
      </li>
    </ul>

    <!-- Accounts link -->
    <div class="px-2 py-1 border-t border-gray-800">
      <RouterLink
        to="/accounts"
        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition w-full"
        active-class="bg-gray-800 text-white"
      >
        ⚙️ Manage Accounts
      </RouterLink>
    </div>

    <!-- Sync status -->
    <div class="p-3 border-t border-gray-800 text-xs text-gray-500">
      <span v-if="accounts.activeAccount?.last_synced_at">
        Last sync: {{ new Date(accounts.activeAccount.last_synced_at).toLocaleTimeString() }}
      </span>
      <span v-else>Never synced</span>
    </div>
  </nav>
</template>
