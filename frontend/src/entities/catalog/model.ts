import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as catalogApi from './api'
import type { Breed, Species } from './types'

export const useCatalogStore = defineStore('catalog', () => {
  const species = ref<Species[]>([])
  const isLoaded = ref(false)
  const breedsBySpecies = ref<Map<number, Breed[]>>(new Map())

  async function ensureSpeciesLoaded(): Promise<void> {
    if (isLoaded.value) return

    const response = await catalogApi.listSpecies()
    species.value = response.data
    isLoaded.value = true
  }

  function speciesName(speciesId: number): string {
    return species.value.find((s) => s.id === speciesId)?.name ?? '—'
  }

  async function ensureBreedsLoaded(speciesId: number): Promise<void> {
    if (breedsBySpecies.value.has(speciesId)) return

    const slug = species.value.find((s) => s.id === speciesId)?.slug
    if (!slug) return

    const response = await catalogApi.listBreeds(slug)
    breedsBySpecies.value.set(speciesId, response.data)
  }

  function breedName(speciesId: number, breedId: number): string | null {
    return breedsBySpecies.value.get(speciesId)?.find((b) => b.id === breedId)?.name ?? null
  }

  return { species, ensureSpeciesLoaded, speciesName, ensureBreedsLoaded, breedName }
})
