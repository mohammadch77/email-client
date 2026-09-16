<script setup lang="ts">
import { useComposeStore } from '@/stores/compose'
const compose = useComposeStore()
</script>

<template>
  <Teleport to="body">
    <div
      v-if="compose.isOpen"
      class="fixed bottom-0 right-6 w-[480px] bg-gray-900 rounded-t-xl shadow-2xl border border-gray-700 flex flex-col z-50"
      style="max-height: 520px"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 bg-gray-800 rounded-t-xl shrink-0">
        <span class="text-sm font-medium text-white">
          {{
            compose.state.mode === 'new'
              ? 'New Message'
              : compose.state.mode === 'reply'
                ? 'Reply'
                : compose.state.mode === 'reply-all'
                  ? 'Reply All'
                  : 'Forward'
          }}
        </span>
        <button @click="compose.close()" class="text-gray-400 hover:text-white text-lg leading-none">
          ✕
        </button>
      </div>

      <!-- Fields -->
      <div class="flex flex-col gap-0 border-b border-gray-700 shrink-0">
        <input
          v-model="compose.state.to"
          placeholder="To (comma-separated)"
          class="w-full bg-transparent px-4 py-2 text-sm text-white border-b border-gray-700 focus:outline-none placeholder-gray-500"
        />
        <input
          v-model="compose.state.cc"
          placeholder="CC (optional)"
          class="w-full bg-transparent px-4 py-2 text-sm text-white border-b border-gray-700 focus:outline-none placeholder-gray-500"
        />
        <input
          v-model="compose.state.subject"
          placeholder="Subject"
          class="w-full bg-transparent px-4 py-2 text-sm text-white focus:outline-none placeholder-gray-500"
        />
      </div>

      <!-- Body -->
      <textarea
        v-model="compose.state.body"
        placeholder="Write your message..."
        class="flex-1 bg-transparent px-4 py-3 text-sm text-white resize-none focus:outline-none placeholder-gray-500 min-h-[160px]"
      />

      <!-- Error -->
      <p v-if="compose.error" class="px-4 text-xs text-red-400 shrink-0">
        {{ compose.error }}
      </p>

      <!-- Footer -->
      <div class="px-4 py-3 flex items-center justify-between shrink-0 border-t border-gray-700">
        <button
          @click="compose.send()"
          :disabled="compose.sending"
          class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-5 py-1.5 rounded-lg transition disabled:opacity-50"
        >
          {{ compose.sending ? 'Sending...' : 'Send' }}
        </button>
        <button @click="compose.close()" class="text-gray-500 hover:text-gray-300 text-sm transition">
          Discard
        </button>
      </div>
    </div>
  </Teleport>
</template>
