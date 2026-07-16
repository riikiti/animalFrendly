<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as conversationApi from '@/entities/conversation/api'
import type { Message } from '@/entities/conversation/types'
import { useUserStore } from '@/entities/user/model'
import { echo } from '@/shared/lib/echo'
import { yandexRouteUrl } from '@/shared/lib/directions'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const conversationId = ref<string | null>(null)
const messages = ref<Message[]>([])
const draft = ref('')
const counterpartAddress = ref<string | null>(null)
const counterpartLocation = ref<{ lat: number; lng: number } | null>(null)

onMounted(async () => {
  const kind = String(route.params.kind)
  const id = String(route.params.id)

  const conversation =
    kind === 'adoption'
      ? await conversationApi.getConversationForAdoptionRequest(id)
      : await conversationApi.getConversationForMatch(id)
  conversationId.value = conversation.data.id
  counterpartAddress.value = conversation.data.counterpart_address
  counterpartLocation.value = conversation.data.counterpart_location

  await refreshMessages()

  echo
    .private(`conversation.${conversationId.value}`)
    .listen('.message.sent', (message: Message) => {
      appendMessage(message)
    })
})

// Отправитель добавляет своё сообщение локально сразу по ответу POST — не ждёт эха
// собственного WebSocket-события (под нагрузкой это может занять больше пары секунд, т.к.
// broadcast идёт через ту же очередь, что и остальные джобы). Дедуп по id на случай, если
// WS-событие всё же придёт следом.
function appendMessage(message: Message): void {
  if (messages.value.some((m) => m.id === message.id)) return
  messages.value.push(message)
}

onUnmounted(() => {
  if (conversationId.value) {
    echo.leave(`conversation.${conversationId.value}`)
  }
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
  const response = await conversationApi.sendMessage(conversationId.value, body)
  appendMessage(response.data)
}
</script>

<template>
  <div class="mx-auto flex h-screen max-w-sm flex-col px-4 py-4 md:max-w-lg lg:max-w-2xl lg:px-8">
    <div class="flex items-center gap-2 pb-2">
      <button class="text-ink-soft" @click="router.back()">←</button>
      <span class="text-sm font-semibold text-ink">Чат</span>
    </div>

    <div
      v-if="counterpartAddress"
      class="flex items-center justify-between gap-2 rounded-2xl bg-surface-soft px-3 py-2"
    >
      <span class="text-xs text-ink-soft">📍 {{ counterpartAddress }}</span>
      <a
        v-if="counterpartLocation"
        :href="yandexRouteUrl(counterpartLocation.lat, counterpartLocation.lng)"
        target="_blank"
        rel="noopener"
        class="shrink-0 text-xs font-semibold text-teal"
      >
        Как добраться
      </a>
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
