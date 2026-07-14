import { apiRequest } from '@/shared/api/http'
import type { Breed, Species } from './types'

export function listSpecies(): Promise<{ data: Species[] }> {
  return apiRequest('/api/v1/catalog/species', { auth: false })
}

export function listBreeds(speciesSlug: string): Promise<{ data: Breed[] }> {
  return apiRequest(`/api/v1/catalog/species/${speciesSlug}/breeds`, { auth: false })
}
