import { apiRequest } from '@/shared/api/http'
import type { Pet, PetPhoto } from './types'

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

export function listPetPhotos(petId: string): Promise<{ data: PetPhoto[] }> {
  return apiRequest(`/api/v1/pets/${petId}/photos`)
}

export function addPetPhoto(petId: string, file: File): Promise<{ data: PetPhoto }> {
  const formData = new FormData()
  formData.append('photo', file)

  return apiRequest(`/api/v1/pets/${petId}/photos`, { method: 'POST', body: formData })
}

export function removePetPhoto(petId: string, photoId: string): Promise<{ data: { ok: boolean } }> {
  return apiRequest(`/api/v1/pets/${petId}/photos/${photoId}`, { method: 'DELETE' })
}

export function setPetPhotoCover(petId: string, photoId: string): Promise<{ data: PetPhoto }> {
  return apiRequest(`/api/v1/pets/${petId}/photos/${photoId}/cover`, { method: 'POST' })
}
