export type PetSex = 'male' | 'female'
export type PetPurpose = 'social' | 'breeding' | 'for_sale' | 'shelter'
export type PetStatus = 'active' | 'hidden' | 'archived'
export type PetSocialTag = 'walks' | 'friendship' | 'mating' | 'training' | 'pet_sitting'

export interface Pet {
  id: string
  owner_id: string
  species_id: number
  breed_id: number | null
  name: string
  sex: PetSex
  birthdate: string | null
  purpose: PetPurpose
  description: string | null
  is_vaccinated: boolean
  status: PetStatus
  photo_url: string | null
  social_tags: PetSocialTag[]
}

export interface PetPhoto {
  id: string
  url: string
  is_primary: boolean
  created_at: string
}
