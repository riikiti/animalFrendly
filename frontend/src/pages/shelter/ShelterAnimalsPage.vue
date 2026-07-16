<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as shelterApi from '@/entities/shelter/api'
import type { Shelter, ShelterAnimal } from '@/entities/shelter/types'

const router = useRouter()
const catalogStore = useCatalogStore()

type Tab = 'animals' | 'shelters'

const activeTab = ref<Tab>('animals')
const animals = ref<ShelterAnimal[]>([])
const shelters = ref<Shelter[]>([])
const isLoading = ref(true)

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()
  const [animalsResponse, sheltersResponse] = await Promise.all([
    shelterApi.listAvailableShelterAnimals(),
    shelterApi.listShelters(),
  ])
  animals.value = animalsResponse.data
  shelters.value = sheltersResponse.data
  isLoading.value = false
})

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

function distanceLabel(distanceKm: number | null): string | null {
  return distanceKm === null ? null : `${distanceKm} км`
}

function openAnimal(animalId: string): void {
  router.push({ name: 'shelter-animal-detail', params: { id: animalId } })
}

function openShelter(shelterId: string): void {
  router.push({ name: 'shelter-detail', params: { id: shelterId } })
}

const hasAnimals = computed(() => animals.value.length > 0)
const hasShelters = computed(() => shelters.value.length > 0)
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Приюты</span>
    </div>

    <div class="flex gap-2 px-2">
      <button
        class="flex-1 rounded-full px-3 py-2 text-sm font-semibold transition"
        :class="activeTab === 'animals' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-faint'"
        @click="activeTab = 'animals'"
      >
        Животные
      </button>
      <button
        class="flex-1 rounded-full px-3 py-2 text-sm font-semibold transition"
        :class="activeTab === 'shelters' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-faint'"
        @click="activeTab = 'shelters'"
      >
        Приюты
      </button>
    </div>

    <div v-if="!isLoading && activeTab === 'animals'" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="!hasAnimals"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока нет животных, ищущих дом
      </p>

      <button
        v-for="animal in animals"
        :key="animal.id"
        class="card flex items-center gap-3 rounded-2xl border border-hairline p-3 text-left"
        @click="openAnimal(animal.id)"
      >
        <div
          class="flex h-13 w-13 shrink-0 items-center justify-center rounded-xl bg-teal-soft text-lg font-semibold text-teal"
        >
          {{ (animal.pet?.name ?? '?').charAt(0) }}
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-ink">{{ animal.pet?.name ?? 'Питомец' }}</p>
          <p class="text-xs text-ink-faint">
            {{ animal.pet ? speciesName(animal.pet.species_id) : '' }}
            <span v-if="animal.pet?.is_vaccinated"> · привит</span>
          </p>
          <p v-if="animal.shelter_name" class="text-xs text-ink-faint">
            {{ animal.shelter_name }}
            <span v-if="distanceLabel(animal.distance_km)">
              · {{ distanceLabel(animal.distance_km) }}</span
            >
          </p>
        </div>
      </button>
    </div>

    <div v-if="!isLoading && activeTab === 'shelters'" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="!hasShelters"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока нет доступных приютов
      </p>

      <button
        v-for="shelter in shelters"
        :key="shelter.id"
        class="card flex items-center gap-3 rounded-2xl border border-hairline p-3 text-left"
        @click="openShelter(shelter.id)"
      >
        <div
          class="flex h-13 w-13 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-teal-soft text-lg font-semibold text-teal"
        >
          <img
            v-if="shelter.photo_url"
            :src="shelter.photo_url"
            class="h-full w-full object-cover"
            alt=""
          />
          <span v-else>{{ shelter.legal_name.charAt(0) }}</span>
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-ink">{{ shelter.legal_name }}</p>
          <p class="text-xs text-ink-faint">
            {{ shelter.city }}
            <span v-if="distanceLabel(shelter.distance_km)">
              · {{ distanceLabel(shelter.distance_km) }}</span
            >
          </p>
        </div>
      </button>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
