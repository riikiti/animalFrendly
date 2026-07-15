export interface PetSearchFilters {
  q?: string
  species_id?: number
  breed_id?: number
  sex?: 'male' | 'female'
  purpose?: 'social' | 'breeding' | 'shelter'
  city?: string
  is_vaccinated?: boolean
  page?: number
  per_page?: number
}

export interface ListingSearchFilters {
  q?: string
  species_id?: number
  breed_id?: number
  city?: string
  min_price_amount?: number
  max_price_amount?: number
  page?: number
  per_page?: number
}

export interface PetSearchResult {
  id: string
  name: string
  species_name: string | null
  breed_name: string | null
  sex: 'male' | 'female'
  purpose: string
  city: string | null
  distance_km: number | null
  is_vaccinated: boolean
  is_boosted: boolean
  photo_url: string | null
}

export interface ListingSearchResult {
  id: string
  pet_name: string
  species_name: string | null
  breed_name: string | null
  city: string | null
  distance_km: number | null
  price_amount: number
  currency: string
  photo_url: string | null
}

export interface SearchMeta {
  total: number
  page: number
  per_page: number
}
