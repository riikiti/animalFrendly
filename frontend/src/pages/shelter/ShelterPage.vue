<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalogStore } from '@/entities/catalog/model'
import * as conversationApi from '@/entities/conversation/api'
import * as shelterApi from '@/entities/shelter/api'
import type { ShelterAnimal, ShelterDetails } from '@/entities/shelter/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const catalogStore = useCatalogStore()

const shelter = ref<ShelterDetails | null>(null)
const animals = ref<ShelterAnimal[]>([])
const isLoading = ref(true)
const notFound = ref(false)
const isContacting = ref(false)

const shelterAnimals = computed(() =>
  animals.value.filter((animal) => animal.shelter_id === shelter.value?.id),
)

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()

  try {
    const shelterId = String(route.params.id)
    const [shelterResponse, animalsResponse] = await Promise.all([
      shelterApi.getShelter(shelterId),
      shelterApi.listAvailableShelterAnimals(),
    ])
    shelter.value = shelterResponse.data
    animals.value = animalsResponse.data
  } catch {
    notFound.value = true
  } finally {
    isLoading.value = false
  }
})

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

async function contactShelter(): Promise<void> {
  if (!shelter.value || isContacting.value) return
  isContacting.value = true

  try {
    const response = await conversationApi.createShelterConversation(shelter.value.id)
    await router.push({ name: 'chat', params: { kind: 'shelter', id: response.data.id } })
  } finally {
    isContacting.value = false
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-6 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.back()">←</button>
      <span class="font-display text-xl font-bold text-ink">Приют</span>
    </div>

    <p v-if="notFound" class="rounded-card bg-surface-soft p-6 text-center text-sm text-ink-soft">
      Приют не найден
    </p>

    <template v-else-if="!isLoading && shelter">
      <div
        class="flex h-40 w-full items-center justify-center overflow-hidden rounded-2xl bg-teal-soft text-3xl font-bold text-accent-text"
      >
        <img
          v-if="shelter.photo_url"
          :src="shelter.photo_url"
          class="h-full w-full object-cover"
          alt=""
        />
        <span v-else>{{ shelter.legal_name.charAt(0) }}</span>
      </div>

      <div class="flex flex-col gap-1 px-2">
        <span class="font-display text-xl text-ink">{{ shelter.legal_name }}</span>
        <p v-if="shelter.description" class="text-sm text-ink-soft">{{ shelter.description }}</p>
      </div>

      <div class="flex flex-col gap-1 rounded-2xl bg-surface-soft p-3 text-xs text-ink-soft">
        <span v-if="shelter.address">📍 {{ shelter.address }}</span>
        <span v-if="shelter.phone">☎ {{ shelter.phone }}</span>
        <span v-if="shelter.email">✉ {{ shelter.email }}</span>
      </div>

      <div
        v-if="shelter.owner"
        class="flex items-center gap-3 rounded-card border border-hairline bg-surface p-3"
      >
        <div
          class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-accent-soft text-sm font-bold text-accent-text"
        >
          <img
            v-if="shelter.owner.avatar_url"
            :src="shelter.owner.avatar_url"
            class="h-full w-full object-cover"
            alt=""
          />
          <span v-else>{{ (shelter.owner.name || shelter.owner.phone).charAt(0) }}</span>
        </div>
        <div class="flex flex-col">
          <span class="text-sm font-semibold text-ink">{{
            shelter.owner.name ?? 'Владелец приюта'
          }}</span>
          <span class="text-xs text-ink-faint">{{ shelter.owner.phone }}</span>
        </div>
      </div>

      <div class="px-2">
        <BaseButton :disabled="isContacting" @click="contactShelter">
          {{ isContacting ? 'Открываем чат…' : 'Написать в приют' }}
        </BaseButton>
      </div>

      <div class="flex flex-col gap-2 border-t border-hairline px-2 pt-4">
        <span class="font-display text-base text-ink">Животные приюта</span>
        <p v-if="shelterAnimals.length === 0" class="text-xs text-ink-faint">
          Пока нет животных, ищущих дом
        </p>
        <div
          v-for="animal in shelterAnimals"
          :key="animal.id"
          class="flex items-center gap-3 rounded-card border border-hairline bg-surface p-3"
        >
          <img
            v-if="animal.pet?.photo_url"
            :src="animal.pet.photo_url"
            class="h-11 w-11 shrink-0 rounded-2xl object-cover"
            alt=""
          />
          <div
            v-else
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-sm font-bold text-accent-text"
          >
            {{ (animal.pet?.name ?? '?').charAt(0) }}
          </div>
          <div class="flex-1">
            <p class="text-sm font-semibold text-ink">{{ animal.pet?.name ?? 'Питомец' }}</p>
            <p class="text-xs text-ink-faint">
              {{ animal.pet ? speciesName(animal.pet.species_id) : '' }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
