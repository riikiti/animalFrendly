import { apiRequest } from '@/shared/api/http'
import type { Conversation, Message } from './types'

export function listConversations(): Promise<{ data: Conversation[] }> {
  return apiRequest('/api/v1/conversations')
}

export function getConversationForMatch(matchId: string): Promise<{ data: Conversation }> {
  return apiRequest(`/api/v1/matches/${matchId}/conversation`)
}

export function listMessages(conversationId: string): Promise<{ data: Message[] }> {
  return apiRequest(`/api/v1/conversations/${conversationId}/messages`)
}

export function sendMessage(conversationId: string, body: string): Promise<{ data: Message }> {
  return apiRequest(`/api/v1/conversations/${conversationId}/messages`, {
    method: 'POST',
    body: { body },
  })
}
