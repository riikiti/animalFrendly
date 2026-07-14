export interface Species {
  id: number
  slug: string
  name: string
}

export interface Breed {
  id: number
  species_id: number
  slug: string
  name: string
  attributes: Record<string, unknown>
}
