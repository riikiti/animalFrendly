<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import MatchModal from '@/widgets/MatchModal.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as matchApi from '@/entities/match/api'
import type { PetMatch } from '@/entities/match/types'
import { usePetStore } from '@/entities/pet/model'
import type { Pet } from '@/entities/pet/types'

const router = useRouter()
const petStore = usePetStore()
const catalogStore = useCatalogStore()

type Tab = 'received' | 'sent'

const activeTab = ref<Tab>('received')
const myPet = ref<Pet | null>(null)
const received = ref<Pet[]>([])
const sent = ref<Pet[]>([])
const isLoading = ref(true)
const respondingPetId = ref<string | null>(null)
const currentMatch = ref<PetMatch | null>(null)

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()
  await petStore.fetchMyPets()

  if (petStore.myPets.length === 0) {
    await router.push({ name: 'create-pet' })
    return
  }

  myPet.value = petStore.myPets[0]
  await loadPendingLikes()
  isLoading.value = false
})

async function loadPendingLikes(): Promise<void> {
  if (!myPet.value) return
  const response = await matchApi.listPendingLikes(myPet.value.id)
  received.value = response.data.received
  sent.value = response.data.sent
}

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

async function respond(target: Pet, action: 'like' | 'dislike'): Promise<void> {
  if (!myPet.value || respondingPetId.value) return
  respondingPetId.value = target.id

  try {
    const result = await matchApi.swipe(myPet.value.id, target.id, action)
    received.value = received.value.filter((pet) => pet.id !== target.id)

    if (result.is_match) {
      currentMatch.value = result.match
    }
  } finally {
    respondingPetId.value = null
  }
}

function dismissMatch(): void {
  currentMatch.value = null
}

async function goToChat(): Promise<void> {
  if (!currentMatch.value) return
  await router.push({ name: 'chat', params: { kind: 'match', id: currentMatch.value.id } })
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Лайки</span>
      <button
        class="text-lg text-ink-faint"
        aria-label="Закрыть"
        @click="router.push({ name: 'home' })"
      >
        ✕
      </button>
    </div>

    <div class="flex gap-2 px-2">
      <button
        class="flex-1 rounded-full px-3 py-2 text-sm font-semibold transition"
        :class="activeTab === 'received' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-faint'"
        @click="activeTab = 'received'"
      >
        Лайкнули вас
      </button>
      <button
        class="flex-1 rounded-full px-3 py-2 text-sm font-semibold transition"
        :class="activeTab === 'sent' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-faint'"
        @click="activeTab = 'sent'"
      >
        Вы лайкнули
      </button>
    </div>

    <div v-if="!isLoading && activeTab === 'received'" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="received.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока никто не лайкнул вашу анкету
      </p>

      <div
        v-for="pet in received"
        :key="pet.id"
        class="flex items-center gap-3 rounded-2xl border border-hairline p-3"
      >
        <div
          class="flex h-13 w-13 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-teal-soft text-lg font-semibold text-teal"
        >
          <img
            v-if="pet.photo_url"
            :src="pet.photo_url"
            class="h-full w-full object-cover"
            alt=""
          />
          <span v-else>{{ pet.name.charAt(0) }}</span>
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-ink">{{ pet.name }}</p>
          <p class="text-xs text-ink-faint">{{ speciesName(pet.species_id) }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
          <button
            class="flex h-9 w-9 items-center justify-center rounded-full border border-clay-soft text-sm text-clay disabled:opacity-50"
            :disabled="respondingPetId === pet.id"
            aria-label="Не отвечать"
            @click="respond(pet, 'dislike')"
          >
            ✕
          </button>
          <button
            class="flex h-9 w-9 items-center justify-center rounded-full bg-teal text-sm text-white disabled:opacity-50"
            :disabled="respondingPetId === pet.id"
            aria-label="Лайкнуть в ответ"
            @click="respond(pet, 'like')"
          >
            ♥
          </button>
        </div>
      </div>
    </div>

    <div v-if="!isLoading && activeTab === 'sent'" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="sent.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Вы пока никого не лайкнули
      </p>

      <div
        v-for="pet in sent"
        :key="pet.id"
        class="flex items-center gap-3 rounded-2xl border border-hairline p-3"
      >
        <div
          class="flex h-13 w-13 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-teal-soft text-lg font-semibold text-teal"
        >
          <img
            v-if="pet.photo_url"
            :src="pet.photo_url"
            class="h-full w-full object-cover"
            alt=""
          />
          <span v-else>{{ pet.name.charAt(0) }}</span>
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-ink">{{ pet.name }}</p>
          <p class="text-xs text-ink-faint">{{ speciesName(pet.species_id) }}</p>
        </div>
        <span class="shrink-0 text-xs text-ink-faint">Ждём ответа</span>
      </div>
    </div>

    <MatchModal :open="currentMatch !== null" @continue="dismissMatch" @chat="goToChat" />
  </div>
</template>
