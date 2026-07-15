import { apiRequest } from '@/shared/api/http'
import type {
  ListingSearchFilters,
  ListingSearchResult,
  PetSearchFilters,
  PetSearchResult,
  SearchMeta,
} from './types'

function toQueryString(filters: PetSearchFilters | ListingSearchFilters): string {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(filters)) {
    if (value === undefined || value === null || value === '') continue
    params.set(key, String(value))
  }

  const query = params.toString()
  return query ? `?${query}` : ''
}

export function searchPets(
  filters: PetSearchFilters = {},
): Promise<{ data: PetSearchResult[]; meta: SearchMeta }> {
  return apiRequest(`/api/v1/search/pets${toQueryString(filters)}`)
}

export function searchListings(
  filters: ListingSearchFilters = {},
): Promise<{ data: ListingSearchResult[]; meta: SearchMeta }> {
  return apiRequest(`/api/v1/search/listings${toQueryString(filters)}`)
}
