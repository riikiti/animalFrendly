import { apiRequest } from '@/shared/api/http'
import type { Breeder } from './types'

export function registerBreeder(): Promise<{ data: Breeder }> {
  return apiRequest('/api/v1/breeders', { method: 'POST' })
}

export function getMyBreeder(): Promise<{ data: Breeder | null }> {
  return apiRequest('/api/v1/breeders/me')
}

export function listPendingBreederVerifications(): Promise<{ data: Breeder[] }> {
  return apiRequest('/api/v1/breeders/pending-verification')
}

export function verifyBreeder(breederId: string, approve: boolean): Promise<{ data: Breeder }> {
  return apiRequest(`/api/v1/breeders/${breederId}/verify`, {
    method: 'POST',
    body: { approve },
  })
}
