<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { MessageCircleDashed } from 'lucide-vue-next'
import * as conversationApi from '@/entities/conversation/api'
import type { Conversation } from '@/entities/conversation/types'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'

/**
 * Список бесед. На телефоне это отдельный экран, на десктопе — левая колонка рядом
 * с перепиской, поэтому список живёт в виджете, а не внутри страницы.
 */
const route = useRoute()
const router = useRouter()

const conversations = ref<Conversation[]>([])
const isLoading = ref(true)

onMounted(async () => {
  conversations.value = (await conversationApi.listConversations()).data
  isLoading.value = false
})

/** Куда ведёт беседа: источник определяет вид маршрута. */
function target(conversation: Conversation): { kind: string; id: string } {
  if (conversation.match_id) return { kind: 'match', id: conversation.match_id }
  if (conversation.adoption_request_id) {
    return { kind: 'adoption', id: conversation.adoption_request_id }
  }
  if (conversation.shelter_id) return { kind: 'shelter', id: conversation.id }

  return { kind: 'direct', id: conversation.id }
}

const openedId = computed(() => String(route.params.id ?? ''))

function open(conversation: Conversation): void {
  router.push({ name: 'chat', params: target(conversation) })
}

function kindLabel(conversation: Conversation): string {
  if (conversation.shelter_id) return 'Приют'
  if (conversation.adoption_request_id) return 'Заявка на пристройство'
  if (conversation.recipient_user_id) return 'Личная переписка'

  return 'Мэтч'
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div v-if="!isLoading" class="flex flex-col gap-2">
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
      class="flex items-center gap-3 rounded-card border p-3 text-left transition-colors"
      :class="
        target(conversation).id === openedId
          ? 'border-accent bg-accent-soft'
          : 'border-hairline bg-surface hover:bg-surface-soft'
      "
      @click="open(conversation)"
    >
      <BaseAvatar
        :src="conversation.counterpart_avatar_url"
        :name="conversation.counterpart_name ?? undefined"
      />
      <div class="min-w-0 flex-1">
        <p class="truncate font-display text-[15px] font-bold text-ink">
          {{ conversation.counterpart_name ?? 'Собеседник' }}
        </p>
        <p class="text-xs text-ink-faint">{{ kindLabel(conversation) }}</p>
      </div>
      <span class="shrink-0 text-xs text-ink-faint">{{ formatDate(conversation.created_at) }}</span>
    </button>
  </div>
</template>
