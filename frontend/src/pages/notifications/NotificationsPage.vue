<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, ChevronLeft } from 'lucide-vue-next'
import { useNotificationStore } from '@/entities/notification/model'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'

const router = useRouter()
const notificationStore = useNotificationStore()
const isLoading = ref(true)

onMounted(async () => {
  await notificationStore.fetchNotifications()
  isLoading.value = false
})

function formatDate(value: string): string {
  return new Date(value).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between gap-2 px-2">
      <div class="flex min-w-0 items-center gap-2">
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Назад"
          @click="router.push({ name: 'home' })"
        >
          <ChevronLeft class="size-5" />
        </button>
        <h1 class="truncate font-display text-xl font-bold text-ink">Уведомления</h1>
      </div>
      <button
        v-if="notificationStore.unreadCount > 0"
        class="shrink-0 text-xs font-bold text-accent-text"
        @click="notificationStore.markAllRead()"
      >
        Прочитать всё
      </button>
    </div>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2">
      <BaseEmptyState
        v-if="notificationStore.items.length === 0"
        tone="neutral"
        title="Пока нет уведомлений"
        description="Здесь появятся мэтчи, заявки и новости по вашим сделкам."
      >
        <template #icon><Bell class="size-8" /></template>
      </BaseEmptyState>

      <button
        v-for="notification in notificationStore.items"
        :key="notification.id"
        type="button"
        class="flex items-start gap-3 rounded-card border p-4 text-left transition-colors"
        :class="
          notification.read_at === null
            ? 'border-transparent bg-accent-soft'
            : 'border-hairline bg-surface'
        "
        @click="notification.read_at === null && notificationStore.markRead(notification.id)"
      >
        <span
          v-if="notification.read_at === null"
          class="mt-1.5 size-2 shrink-0 rounded-full bg-accent"
          aria-hidden="true"
        />
        <span class="flex flex-1 flex-col gap-1">
          <span class="text-sm text-ink">{{ notification.message }}</span>
          <span class="text-xs text-ink-faint">{{ formatDate(notification.created_at) }}</span>
        </span>
      </button>
    </div>

    <BaseButton variant="outline" class="mx-2" @click="router.push({ name: 'home' })"
      >На главную</BaseButton
    >
  </div>
</template>
