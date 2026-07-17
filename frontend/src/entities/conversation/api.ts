import { apiRequest } from '@/shared/api/http'
import type { Conversation, Message } from './types'

export function listConversations(): Promise<{ data: Conversation[] }> {
  return apiRequest('/api/v1/conversations')
}

export function getConversationForMatch(matchId: string): Promise<{ data: Conversation }> {
  return apiRequest(`/api/v1/matches/${matchId}/conversation`)
}

export function getConversationForAdoptionRequest(
  adoptionRequestId: string,
): Promise<{ data: Conversation }> {
  return apiRequest(`/api/v1/adoption-requests/${adoptionRequestId}/conversation`)
}

export function createShelterConversation(
  shelterId: string,
  shelterAnimalId?: string | null,
): Promise<{ data: Conversation }> {
  return apiRequest(`/api/v1/shelters/${shelterId}/conversations`, {
    method: 'POST',
    body: { shelter_animal_id: shelterAnimalId ?? undefined },
  })
}

export function createDirectConversation(userId: string): Promise<{ data: Conversation }> {
  return apiRequest(`/api/v1/users/${userId}/conversations`, { method: 'POST' })
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
