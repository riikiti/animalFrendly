import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as catalogApi from './api'
import type { Species } from './types'

export const useCatalogStore = defineStore('catalog', () => {
  const species = ref<Species[]>([])
  const isLoaded = ref(false)

  async function ensureSpeciesLoaded(): Promise<void> {
    if (isLoaded.value) return

    const response = await catalogApi.listSpecies()
    species.value = response.data
    isLoaded.value = true
  }

  function speciesName(speciesId: number): string {
    return species.value.find((s) => s.id === speciesId)?.name ?? '—'
  }

  return { species, ensureSpeciesLoaded, speciesName }
})
