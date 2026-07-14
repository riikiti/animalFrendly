import { apiRequest } from '@/shared/api/http'
import type { AdoptionRequest, ShelterAnimal } from './types'

export function listAvailableShelterAnimals(): Promise<{ data: ShelterAnimal[] }> {
  return apiRequest('/api/v1/shelter-animals')
}

export function submitAdoptionRequest(
  shelterAnimalId: string,
  message: string,
): Promise<{ data: AdoptionRequest }> {
  return apiRequest(`/api/v1/shelter-animals/${shelterAnimalId}/adoption-requests`, {
    method: 'POST',
    body: { message: message || undefined },
  })
}

export function listMyAdoptionRequests(): Promise<{ data: AdoptionRequest[] }> {
  return apiRequest('/api/v1/adoption-requests/me')
}

export function cancelAdoptionRequest(
  adoptionRequestId: string,
): Promise<{ data: AdoptionRequest }> {
  return apiRequest(`/api/v1/adoption-requests/${adoptionRequestId}/cancel`, { method: 'POST' })
}
