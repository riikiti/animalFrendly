<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/entities/notification/model'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

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
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6">
    <div class="flex items-center justify-between px-2">
      <div class="flex items-center gap-3">
        <button class="text-sm text-ink-faint" @click="router.push({ name: 'home' })">←</button>
        <span class="font-display text-lg text-ink">Уведомления</span>
      </div>
      <button
        v-if="notificationStore.unreadCount > 0"
        class="text-xs font-semibold text-teal"
        @click="notificationStore.markAllRead()"
      >
        Прочитать всё
      </button>
    </div>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2">
      <p
        v-if="notificationStore.items.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока нет уведомлений
      </p>

      <button
        v-for="notification in notificationStore.items"
        :key="notification.id"
        type="button"
        class="flex flex-col gap-1 rounded-2xl border border-hairline p-4 text-left"
        :class="notification.read_at === null ? 'bg-teal-soft' : 'bg-surface'"
        @click="notification.read_at === null && notificationStore.markRead(notification.id)"
      >
        <span class="text-sm text-ink">{{ notification.message }}</span>
        <span class="text-xs text-ink-faint">{{ formatDate(notification.created_at) }}</span>
      </button>
    </div>

    <BaseButton variant="outline" @click="router.push({ name: 'home' })">На главную</BaseButton>
  </div>
</template>
