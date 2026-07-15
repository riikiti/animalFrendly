import { apiRequest } from '@/shared/api/http'
import type { Pet } from './types'

export interface CreatePetPayload {
  species_id: number
  breed_id?: number | null
  name: string
  sex: 'male' | 'female'
  purpose: 'social' | 'breeding'
  birthdate?: string | null
  description?: string | null
  is_vaccinated?: boolean
}

export function listMyPets(): Promise<{ data: Pet[] }> {
  return apiRequest('/api/v1/pets')
}

export function createPet(payload: CreatePetPayload): Promise<{ data: Pet }> {
  return apiRequest('/api/v1/pets', { method: 'POST', body: payload })
}

export function uploadPetPhoto(petId: string, file: File): Promise<{ data: Pet }> {
  const formData = new FormData()
  formData.append('photo', file)

  return apiRequest(`/api/v1/pets/${petId}/photo`, { method: 'POST', body: formData })
}

export function removePetPhoto(petId: string): Promise<{ data: Pet }> {
  return apiRequest(`/api/v1/pets/${petId}/photo`, { method: 'DELETE' })
}
