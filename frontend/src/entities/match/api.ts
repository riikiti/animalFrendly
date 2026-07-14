import { apiRequest } from '@/shared/api/http'
import type { Pet } from '@/entities/pet/types'
import type { PetMatch } from './types'

export type SwipeAction = 'like' | 'dislike' | 'super_like'

export interface SwipeResult {
  is_match: boolean
  match: PetMatch | null
}

export function listCandidates(petId: string): Promise<{ data: Pet[] }> {
  return apiRequest(`/api/v1/pets/${petId}/candidates`)
}

export function swipe(
  petId: string,
  targetPetId: string,
  action: SwipeAction,
): Promise<SwipeResult> {
  return apiRequest(`/api/v1/pets/${petId}/swipes`, {
    method: 'POST',
    body: { target_pet_id: targetPetId, action },
  })
}
