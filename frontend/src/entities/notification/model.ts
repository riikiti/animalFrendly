import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as notificationApi from './api'
import type { Notification } from './types'

export const useNotificationStore = defineStore('notification', () => {
  const items = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  async function fetchNotifications(): Promise<void> {
    isLoading.value = true
    try {
      const response = await notificationApi.listNotifications()
      items.value = response.data
    } finally {
      isLoading.value = false
    }
  }

  async function refreshUnreadCount(): Promise<void> {
    const response = await notificationApi.unreadCount()
    unreadCount.value = response.data.count
  }

  async function markRead(notificationId: string): Promise<void> {
    const response = await notificationApi.markRead(notificationId)
    const index = items.value.findIndex((n) => n.id === notificationId)
    if (index !== -1) items.value[index] = response.data
    await refreshUnreadCount()
  }

  async function markAllRead(): Promise<void> {
    await notificationApi.markAllRead()
    items.value = items.value.map((n) => ({ ...n, read_at: n.read_at ?? new Date().toISOString() }))
    unreadCount.value = 0
  }

  // Дедуп по id — на случай повторной доставки WS-события после переподключения (тот же
  // приём, что appendMessage в ChatPage.vue).
  function pushIncoming(notification: Notification): void {
    if (items.value.some((n) => n.id === notification.id)) return
    items.value.unshift(notification)
    if (notification.read_at === null) unreadCount.value++
  }

  return {
    items,
    unreadCount,
    isLoading,
    fetchNotifications,
    refreshUnreadCount,
    markRead,
    markAllRead,
    pushIncoming,
  }
})
