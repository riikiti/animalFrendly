<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { MessageCircleDashed, X } from 'lucide-vue-next'
import * as conversationApi from '@/entities/conversation/api'
import type { Conversation } from '@/entities/conversation/types'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'

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
  } else if (conversation.shelter_id) {
    router.push({ name: 'chat', params: { kind: 'shelter', id: conversation.id } })
  } else {
    router.push({ name: 'chat', params: { kind: 'direct', id: conversation.id } })
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <h1 class="font-display text-xl font-bold text-ink">Чаты</h1>
      <button
        class="grid size-9 place-items-center rounded-full text-ink-faint transition-colors hover:bg-surface-soft"
        aria-label="Закрыть"
        @click="router.push({ name: 'home' })"
      >
        <X class="size-5" />
      </button>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-2 px-2 pb-4">
      <BaseEmptyState
        v-if="conversations.length === 0"
        tone="teal"
        title="Пока нет ни одной беседы"
        description="Как только появится мэтч, вы сможете написать первым."
      >
        <template #icon><MessageCircleDashed class="size-8" /></template>
      </BaseEmptyState>

      <button
        v-for="conversation in conversations"
        :key="conversation.id"
        type="button"
        class="flex items-center gap-3 rounded-card border border-hairline bg-surface p-3 text-left transition-colors hover:bg-surface-soft"
        @click="openConversation(conversation)"
      >
        <BaseAvatar
          :src="conversation.counterpart_avatar_url"
          :name="conversation.counterpart_name ?? undefined"
        />
        <div class="min-w-0 flex-1">
          <p class="truncate font-display text-[15px] font-bold text-ink">
            {{ conversation.counterpart_name ?? 'Собеседник' }}
          </p>
          <p class="text-xs text-ink-faint">
            {{
              conversation.shelter_id
                ? 'Приют'
                : conversation.adoption_request_id
                  ? 'Заявка на пристройство'
                  : conversation.recipient_user_id
                    ? 'Личная переписка'
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
