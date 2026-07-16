import { apiRequest } from '@/shared/api/http'
import type { AdoptionRequest, Shelter, ShelterAnimal, ShelterDetails } from './types'

export function listAvailableShelterAnimals(): Promise<{ data: ShelterAnimal[] }> {
  return apiRequest('/api/v1/shelter-animals')
}

export function createShelter(payload: {
  legal_name: string
  inn?: string | null
  description?: string | null
}): Promise<{ data: Shelter }> {
  return apiRequest('/api/v1/shelters', { method: 'POST', body: payload })
}

export function getMyShelter(): Promise<{ data: Shelter | null }> {
  return apiRequest('/api/v1/shelters/me')
}

export function getShelter(shelterId: string): Promise<{ data: ShelterDetails }> {
  return apiRequest(`/api/v1/shelters/${shelterId}`)
}

export function updateShelter(
  shelterId: string,
  payload: { phone?: string | null; email?: string | null; address?: string | null },
): Promise<{ data: Shelter }> {
  return apiRequest(`/api/v1/shelters/${shelterId}`, { method: 'PATCH', body: payload })
}

export function uploadShelterPhoto(shelterId: string, file: File): Promise<{ data: Shelter }> {
  const formData = new FormData()
  formData.append('photo', file)

  return apiRequest(`/api/v1/shelters/${shelterId}/photo`, { method: 'POST', body: formData })
}

export function listMyShelterAnimals(shelterId: string): Promise<{ data: ShelterAnimal[] }> {
  return apiRequest(`/api/v1/shelters/${shelterId}/animals`)
}

export function addShelterAnimal(
  shelterId: string,
  payload: {
    species_id: number
    breed_id?: number | null
    name: string
    sex: 'male' | 'female'
    birthdate?: string | null
    description?: string | null
    is_vaccinated?: boolean
  },
): Promise<{ data: ShelterAnimal }> {
  return apiRequest(`/api/v1/shelters/${shelterId}/animals`, { method: 'POST', body: payload })
}

export function listPendingRequestsForShelter(
  shelterId: string,
): Promise<{ data: AdoptionRequest[] }> {
  return apiRequest(`/api/v1/shelters/${shelterId}/adoption-requests/pending`)
}

export function decideAdoptionRequest(
  adoptionRequestId: string,
  approve: boolean,
): Promise<{ data: AdoptionRequest }> {
  return apiRequest(`/api/v1/adoption-requests/${adoptionRequestId}/decide`, {
    method: 'POST',
    body: { approve },
  })
}

export function listPendingShelterVerifications(): Promise<{ data: Shelter[] }> {
  return apiRequest('/api/v1/shelters/pending-verification')
}

export function verifyShelter(shelterId: string, approve: boolean): Promise<{ data: Shelter }> {
  return apiRequest(`/api/v1/shelters/${shelterId}/verify`, { method: 'POST', body: { approve } })
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
