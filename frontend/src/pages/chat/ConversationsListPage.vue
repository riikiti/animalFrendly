<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as conversationApi from '@/entities/conversation/api'
import type { Conversation } from '@/entities/conversation/types'

const router = useRouter()

const conversations = ref<Conversation[]>([])
const isLoading = ref(true)

onMounted(async () => {
  const response = await conversationApi.listConversations()
  conversations.value = response.data
  isLoading.value = false
})

function openConversation(conversation: Conversation): void {
  if (conversation.match_id) {
    router.push({ name: 'chat', params: { kind: 'match', id: conversation.match_id } })
  } else if (conversation.adoption_request_id) {
    router.push({
      name: 'chat',
      params: { kind: 'adoption', id: conversation.adoption_request_id },
    })
  } else {
    router.push({ name: 'chat', params: { kind: 'shelter', id: conversation.id } })
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Чаты</span>
      <button
        class="text-lg text-ink-faint"
        aria-label="Закрыть"
        @click="router.push({ name: 'home' })"
      >
        ✕
      </button>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-2 px-2 pb-4">
      <p
        v-if="conversations.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока нет ни одной беседы
      </p>

      <button
        v-for="conversation in conversations"
        :key="conversation.id"
        type="button"
        class="flex items-center gap-3 rounded-2xl border border-hairline p-3 text-left"
        @click="openConversation(conversation)"
      >
        <div
          class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-teal-soft text-sm font-semibold text-teal"
        >
          <img
            v-if="conversation.counterpart_avatar_url"
            :src="conversation.counterpart_avatar_url"
            class="h-full w-full object-cover"
            alt=""
          />
          <span v-else>{{ (conversation.counterpart_name ?? '?').charAt(0) }}</span>
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-ink">
            {{ conversation.counterpart_name ?? 'Собеседник' }}
          </p>
          <p class="text-xs text-ink-faint">
            {{
              conversation.shelter_id
                ? 'Приют'
                : conversation.adoption_request_id
                  ? 'Заявка на пристройство'
                  : 'Мэтч'
            }}
          </p>
        </div>
        <span class="shrink-0 text-xs text-ink-faint">{{
          formatDate(conversation.created_at)
        }}</span>
      </button>
    </div>
  </div>
</template>
