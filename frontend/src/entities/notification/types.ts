export type NotificationType = 'new_match' | 'new_message' | 'adoption_approved' | 'deal_completed'

export interface Notification {
  id: string
  type: NotificationType
  message: string
  data: Record<string, unknown>
  read_at: string | null
  created_at: string
}
