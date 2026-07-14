export type ShelterAnimalStatus = 'available' | 'reserved' | 'adopted' | 'removed'
export type AdoptionRequestStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'

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
