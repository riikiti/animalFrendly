import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as petApi from './api'
import type { Pet } from './types'

export const usePetStore = defineStore('pet', () => {
  const myPets = ref<Pet[]>([])
  const isLoading = ref(false)

  async function fetchMyPets(): Promise<void> {
    isLoading.value = true
    try {
      const response = await petApi.listMyPets()
      myPets.value = response.data
    } finally {
      isLoading.value = false
    }
  }

  async function createPet(payload: petApi.CreatePetPayload): Promise<Pet> {
    const response = await petApi.createPet(payload)
    myPets.value.push(response.data)

    return response.data
  }

  return { myPets, isLoading, fetchMyPets, createPet }
})
