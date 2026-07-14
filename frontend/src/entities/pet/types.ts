export type PetSex = 'male' | 'female'
export type PetPurpose = 'social' | 'breeding' | 'for_sale' | 'shelter'
export type PetStatus = 'active' | 'hidden' | 'archived'

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
}
