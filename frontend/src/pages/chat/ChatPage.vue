<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as conversationApi from '@/entities/conversation/api'
import type { Message } from '@/entities/conversation/types'
import { useUserStore } from '@/entities/user/model'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const conversationId = ref<string | null>(null)
const messages = ref<Message[]>([])
const draft = ref('')
let pollTimer: ReturnType<typeof setInterval> | undefined

onMounted(async () => {
  const matchId = String(route.params.matchId)
  const conversation = await conversationApi.getConversationForMatch(matchId)
  conversationId.value = conversation.data.id

  await refreshMessages()
  pollTimer = setInterval(refreshMessages, 3000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

async function refreshMessages(): Promise<void> {
  if (!conversationId.value) return
  const response = await conversationApi.listMessages(conversationId.value)
  messages.value = response.data
}

async function send(): Promise<void> {
  if (!conversationId.value || draft.value.trim() === '') return

  const body = draft.value
  draft.value = ''
  await conversationApi.sendMessage(conversationId.value, body)
  await refreshMessages()
}
</script>

<template>
  <div class="mx-auto flex h-screen max-w-sm flex-col px-4 py-4">
    <div class="flex items-center gap-2 pb-2">
      <button class="text-ink-soft" @click="router.back()">←</button>
      <span class="text-sm font-semibold text-ink">Чат</span>
    </div>

    <div class="flex-1 space-y-2 overflow-y-auto py-2">
      <div
        v-for="message in messages"
        :key="message.id"
        class="max-w-[76%] rounded-2xl px-3 py-2 text-sm"
        :class="
          message.sender_id === userStore.currentUser?.id
            ? 'ml-auto rounded-br-sm bg-teal text-white'
            : 'rounded-bl-sm bg-surface-soft text-ink'
        "
      >
        {{ message.body }}
      </div>
    </div>

    <form
      v-if="conversationId"
      class="flex items-center gap-2 rounded-full bg-surface-soft px-3 py-2"
      @submit.prevent="send"
    >
      <input
        v-model="draft"
        type="text"
        placeholder="Сообщение…"
        class="flex-1 bg-transparent text-sm text-ink outline-none"
      />
      <button
        type="submit"
        class="flex h-7 w-7 items-center justify-center rounded-full bg-teal text-xs text-white"
      >
        ➤
      </button>
    </form>
  </div>
</template>
