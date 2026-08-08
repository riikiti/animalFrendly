<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import MatchModal from '@/widgets/MatchModal.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as matchApi from '@/entities/match/api'
import type { PetMatch } from '@/entities/match/types'
import { usePetStore } from '@/entities/pet/model'
import type { Pet } from '@/entities/pet/types'
import { ApiError } from '@/shared/api/http'
import { Heart, HeartCrack, X } from 'lucide-vue-next'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseIconButton from '@/shared/ui/components/BaseIconButton.vue'
import BaseSegmented from '@/shared/ui/components/BaseSegmented.vue'
import PaywallSheet from '@/shared/ui/components/PaywallSheet.vue'

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
const paywallOpen = ref(false)
const paywallMessage = ref('')

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
  } catch (e) {
    if (e instanceof ApiError && e.status === 402) {
      paywallMessage.value = e.message
      paywallOpen.value = true
      return
    }
    throw e
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

function closePaywall(): void {
  paywallOpen.value = false
}

async function goToSubscriptionPlans(): Promise<void> {
  paywallOpen.value = false
  await router.push({ name: 'subscription-plans' })
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <h1 class="font-display text-xl font-bold text-ink">Лайки</h1>
      <button
        class="grid size-9 place-items-center rounded-full text-ink-faint transition-colors hover:bg-surface-soft"
        aria-label="Закрыть"
        @click="router.push({ name: 'home' })"
      >
        <X class="size-5" />
      </button>
    </div>

    <div class="px-2">
      <BaseSegmented
        v-model="activeTab"
        aria-label="Лайки"
        :options="[
          { value: 'received', label: 'Лайкнули вас' },
          { value: 'sent', label: 'Вы лайкнули' },
        ]"
      />
    </div>

    <div v-if="!isLoading && activeTab === 'received'" class="flex flex-1 flex-col gap-2.5 pb-4">
      <BaseEmptyState
        v-if="received.length === 0"
        title="Пока никто не лайкнул вашу анкету"
        description="Продолжайте смотреть анкеты — взаимные симпатии появятся здесь."
      >
        <template #icon><Heart class="size-8" /></template>
      </BaseEmptyState>

      <div
        v-for="pet in received"
        :key="pet.id"
        class="flex items-center gap-3 rounded-card border border-hairline bg-surface p-3"
      >
        <BaseAvatar :src="pet.photo_url" :name="pet.name" size="lg" shape="rounded" />
        <div class="min-w-0 flex-1">
          <p class="truncate font-display text-[15px] font-bold text-ink">{{ pet.name }}</p>
          <p class="text-xs text-ink-faint">{{ speciesName(pet.species_id) }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
          <BaseIconButton
            label="Не отвечать"
            size="sm"
            tone="danger"
            :disabled="respondingPetId === pet.id"
            @click="respond(pet, 'dislike')"
          >
            <HeartCrack class="size-4" />
          </BaseIconButton>
          <BaseIconButton
            label="Лайкнуть в ответ"
            size="sm"
            tone="active"
            :disabled="respondingPetId === pet.id"
            @click="respond(pet, 'like')"
          >
            <Heart class="size-4 fill-current" />
          </BaseIconButton>
        </div>
      </div>
    </div>

    <div v-if="!isLoading && activeTab === 'sent'" class="flex flex-1 flex-col gap-2.5 pb-4">
      <BaseEmptyState
        v-if="sent.length === 0"
        tone="neutral"
        title="Вы пока никого не лайкнули"
        description="Лайкните анкету в ленте — она появится в этом списке."
      >
        <template #icon><Heart class="size-8" /></template>
      </BaseEmptyState>

      <div
        v-for="pet in sent"
        :key="pet.id"
        class="flex items-center gap-3 rounded-card border border-hairline bg-surface p-3"
      >
        <BaseAvatar :src="pet.photo_url" :name="pet.name" size="lg" shape="rounded" />
        <div class="min-w-0 flex-1">
          <p class="truncate font-display text-[15px] font-bold text-ink">{{ pet.name }}</p>
          <p class="text-xs text-ink-faint">{{ speciesName(pet.species_id) }}</p>
        </div>
        <span class="shrink-0 text-xs text-ink-faint">Ждём ответа</span>
      </div>
    </div>

    <MatchModal :open="currentMatch !== null" @continue="dismissMatch" @chat="goToChat" />
    <PaywallSheet
      :open="paywallOpen"
      :message="paywallMessage"
      @close="closePaywall"
      @upgrade="goToSubscriptionPlans"
    />
  </div>
</template>
