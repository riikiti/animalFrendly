export type ShelterAnimalStatus = 'available' | 'reserved' | 'adopted' | 'removed'
export type AdoptionRequestStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'
export type ShelterVerificationStatus = 'pending' | 'verified' | 'rejected'

export interface Shelter {
  id: string
  owner_user_id: string
  legal_name: string
  inn: string | null
  description: string | null
  verification_status: ShelterVerificationStatus
  verified_at: string | null
  address: string | null
  city: string | null
  phone: string | null
  email: string | null
  photo_url: string | null
}

export interface ShelterOwner {
  name: string | null
  phone: string
  avatar_url: string | null
}

export interface ShelterDetails extends Shelter {
  owner: ShelterOwner | null
}

export interface ShelterAnimalPet {
  name: string
  species_id: number
  breed_id: number | null
  sex: 'male' | 'female'
  description: string | null
  is_vaccinated: boolean
}

export interface ShelterAnimal {
  id: string
  shelter_id: string
  pet_id: string
  status: ShelterAnimalStatus
  pet: ShelterAnimalPet | null
}

export interface AdoptionRequest {
  id: string
  shelter_animal_id: string
  requester_user_id: string
  message: string | null
  status: AdoptionRequestStatus
  decided_at: string | null
}
