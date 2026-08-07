<script setup lang="ts">
import { watch } from 'vue'
import { RouterView } from 'vue-router'
import { useNotificationStore } from '@/entities/notification/model'
import type { Notification } from '@/entities/notification/types'
import { useUserStore } from '@/entities/user/model'
import { echo } from '@/shared/lib/echo'
import ToastHost from '@/widgets/ToastHost.vue'

const userStore = useUserStore()
const notificationStore = useNotificationStore()

// Подписка на весь сеанс работы приложения (в отличие от чата, где канал живёт по одной
// беседе на странице) — колокольчик с бейджем виден на нескольких страницах, а не только
// там, где пользователь оставался всё время.
watch(
  () => userStore.currentUser?.id ?? null,
  (userId, previousUserId) => {
    if (previousUserId) {
      echo.leave(`user.${previousUserId}`)
    }
    if (userId) {
      echo
        .private(`user.${userId}`)
        .listen('.notification.created', (notification: Notification) => {
          notificationStore.pushIncoming(notification)
        })
    }
  },
  { immediate: true },
)
</script>

<template>
  <RouterView />
  <ToastHost />
</template>
