<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalogStore } from '@/entities/catalog/model'
import * as shelterApi from '@/entities/shelter/api'
import type { ShelterAnimal } from '@/entities/shelter/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { ApiError } from '@/shared/api/http'

const route = useRoute()
const router = useRouter()
const catalogStore = useCatalogStore()

const animal = ref<ShelterAnimal | null>(null)
const isLoading = ref(true)
const notFound = ref(false)

const isRequestFormOpen = ref(false)
const message = ref('')
const isSubmitted = ref(false)
const error = ref('')

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()

  try {
    const animalId = String(route.params.id)
    const response = await shelterApi.listAvailableShelterAnimals()
    const found = response.data.find((item) => item.id === animalId)

    if (!found) {
      notFound.value = true
    } else {
      animal.value = found
    }
  } finally {
    isLoading.value = false
  }
})

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

const sexLabel = computed(() =>
  animal.value?.pet?.sex === 'female' ? 'Девочка' : animal.value?.pet ? 'Мальчик' : '',
)

const distanceLabel = computed(() =>
  animal.value?.distance_km === null || animal.value?.distance_km === undefined
    ? null
    : `${animal.value.distance_km} км`,
)

function contactShelter(): void {
  if (!animal.value) return
  router.push({ name: 'shelter-detail', params: { id: animal.value.shelter_id } })
}

function openRequestForm(): void {
  isRequestFormOpen.value = true
  message.value = ''
  error.value = ''
}

async function submitRequest(): Promise<void> {
  if (!animal.value) return
  error.value = ''

  try {
    await shelterApi.submitAdoptionRequest(animal.value.id, message.value)
    isSubmitted.value = true
    isRequestFormOpen.value = false
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-6 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.back()">←</button>
      <span class="font-display text-lg text-ink">Анкета питомца</span>
    </div>

    <p v-if="notFound" class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint">
      Питомец не найден
    </p>

    <template v-else-if="!isLoading && animal">
      <div
        class="flex h-40 w-full items-center justify-center rounded-2xl bg-teal-soft text-3xl font-semibold text-teal"
      >
        {{ (animal.pet?.name ?? '?').charAt(0) }}
      </div>

      <div class="flex flex-col gap-1 px-2">
        <span class="font-display text-xl text-ink">{{ animal.pet?.name ?? 'Питомец' }}</span>
        <p class="text-sm text-ink-soft">
          {{ animal.pet ? speciesName(animal.pet.species_id) : '' }}
          <span v-if="sexLabel"> · {{ sexLabel }}</span>
          <span v-if="animal.pet?.is_vaccinated"> · привит</span>
        </p>
        <p v-if="animal.pet?.description" class="text-sm text-ink-soft">
          {{ animal.pet.description }}
        </p>
      </div>

      <div
        v-if="animal.shelter_name"
        class="flex flex-col gap-1 rounded-2xl bg-surface-soft p-3 text-xs text-ink-soft"
      >
        <span>{{ animal.shelter_name }}</span>
        <span v-if="animal.shelter_city">{{ animal.shelter_city }}</span>
        <span v-if="distanceLabel">{{ distanceLabel }} от вас</span>
      </div>

      <div class="flex flex-col gap-2 px-2">
        <BaseButton @click="contactShelter">Связаться</BaseButton>

        <p v-if="isSubmitted" class="text-xs font-semibold text-teal">Заявка отправлена</p>
        <BaseButton v-else-if="!isRequestFormOpen" variant="outline" @click="openRequestForm">
          Оставить заявку
        </BaseButton>

        <div v-if="isRequestFormOpen" class="flex flex-col gap-2">
          <textarea
            v-model="message"
            rows="3"
            placeholder="Расскажите о себе и опыте содержания животных"
            class="rounded-xl bg-surface-soft px-3 py-2 text-sm text-ink outline-none"
          ></textarea>
          <p v-if="error" class="text-xs text-danger">{{ error }}</p>
          <div class="flex gap-2">
            <BaseButton type="submit" @click="submitRequest">Отправить</BaseButton>
            <BaseButton variant="ghost" @click="isRequestFormOpen = false">Отмена</BaseButton>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
