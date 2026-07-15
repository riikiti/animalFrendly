import { apiRequest } from '@/shared/api/http'
import type { Notification } from './types'

export function listNotifications(): Promise<{ data: Notification[] }> {
  return apiRequest('/api/v1/notifications')
}

export function unreadCount(): Promise<{ data: { count: number } }> {
  return apiRequest('/api/v1/notifications/unread-count')
}

export function markRead(notificationId: string): Promise<{ data: Notification }> {
  return apiRequest(`/api/v1/notifications/${notificationId}/read`, { method: 'POST' })
}

export function markAllRead(): Promise<{ data: { ok: boolean } }> {
  return apiRequest('/api/v1/notifications/read-all', { method: 'POST' })
}
