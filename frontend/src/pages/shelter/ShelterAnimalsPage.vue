<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BottomNav from '@/widgets/BottomNav.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as shelterApi from '@/entities/shelter/api'
import type { ShelterAnimal } from '@/entities/shelter/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { ApiError } from '@/shared/api/http'

const catalogStore = useCatalogStore()

const animals = ref<ShelterAnimal[]>([])
const isLoading = ref(true)
const activeAnimalId = ref<string | null>(null)
const message = ref('')
const submittedIds = ref<Set<string>>(new Set())
const error = ref('')

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()
  const response = await shelterApi.listAvailableShelterAnimals()
  animals.value = response.data
  isLoading.value = false
})

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

function openRequestForm(animalId: string): void {
  activeAnimalId.value = animalId
  message.value = ''
  error.value = ''
}

async function submitRequest(animalId: string): Promise<void> {
  error.value = ''

  try {
    await shelterApi.submitAdoptionRequest(animalId, message.value)
    submittedIds.value = new Set([...submittedIds.value, animalId])
    activeAnimalId.value = null
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}

const hasAnimals = computed(() => animals.value.length > 0)
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6">
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Приюты</span>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="!hasAnimals"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока нет животных, ищущих дом
      </p>

      <div
        v-for="animal in animals"
        :key="animal.id"
        class="card flex flex-col gap-2 rounded-2xl border border-hairline p-3"
      >
        <div class="flex items-center gap-3">
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
          </div>
          <span
            class="chip rounded-full bg-accent/20 px-2 py-1 text-xs font-semibold text-accent-ink"
          >
            Ищет дом
          </span>
        </div>
        <p v-if="animal.pet?.description" class="text-xs text-ink-soft">
          {{ animal.pet.description }}
        </p>

        <p v-if="submittedIds.has(animal.id)" class="text-xs font-semibold text-teal">
          Заявка отправлена
        </p>
        <BaseButton
          v-else-if="activeAnimalId !== animal.id"
          variant="outline"
          @click="openRequestForm(animal.id)"
        >
          Оставить заявку
        </BaseButton>

        <div v-if="activeAnimalId === animal.id" class="flex flex-col gap-2">
          <textarea
            v-model="message"
            rows="3"
            placeholder="Расскажите о себе и опыте содержания животных"
            class="rounded-xl bg-surface-soft px-3 py-2 text-sm text-ink outline-none"
          ></textarea>
          <p v-if="error" class="text-xs text-danger">{{ error }}</p>
          <div class="flex gap-2">
            <BaseButton @click="submitRequest(animal.id)">Отправить</BaseButton>
            <BaseButton variant="ghost" @click="activeAnimalId = null">Отмена</BaseButton>
          </div>
        </div>
      </div>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
