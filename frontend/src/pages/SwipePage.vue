<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import MatchModal from '@/widgets/MatchModal.vue'
import PetCard from '@/widgets/PetCard.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as matchApi from '@/entities/match/api'
import type { PetMatch } from '@/entities/match/types'
import { usePetStore } from '@/entities/pet/model'
import type { Pet } from '@/entities/pet/types'
import { useUserStore } from '@/entities/user/model'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const router = useRouter()
const petStore = usePetStore()
const catalogStore = useCatalogStore()
const userStore = useUserStore()

const myPet = ref<Pet | null>(null)
const candidates = ref<Pet[]>([])
const currentMatch = ref<PetMatch | null>(null)
const isLoading = ref(true)

// Пользователь может выйти (см. onLogout) прежде, чем эта цепочка успеет завершиться —
// тогда токен уже очищен, и последующие запросы закономерно получат 401. Не считаем это
// необработанной ошибкой: страница уже покидается, реагировать не на что.
let isMounted = true
onUnmounted(() => {
  isMounted = false
})

onMounted(async () => {
  try {
    await catalogStore.ensureSpeciesLoaded()
    await petStore.fetchMyPets()

    if (petStore.myPets.length === 0) {
      await router.push({ name: 'create-pet' })
      return
    }

    myPet.value = petStore.myPets[0]
    await loadCandidates()
    isLoading.value = false
  } catch (error) {
    if (isMounted) throw error
  }
})

async function loadCandidates(): Promise<void> {
  if (!myPet.value) return
  const response = await matchApi.listCandidates(myPet.value.id)
  candidates.value = response.data
}

async function onSwipe(action: matchApi.SwipeAction): Promise<void> {
  if (!myPet.value || candidates.value.length === 0) return

  const target = candidates.value[0]
  const result = await matchApi.swipe(myPet.value.id, target.id, action)
  candidates.value = candidates.value.slice(1)

  if (result.is_match) {
    currentMatch.value = result.match
  }
}

function dismissMatch(): void {
  currentMatch.value = null
}

async function goToChat(): Promise<void> {
  if (!currentMatch.value) return
  await router.push({ name: 'chat', params: { matchId: currentMatch.value.id } })
}

async function onLogout(): Promise<void> {
  await userStore.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 py-6">
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">AnimalFriendly</span>
      <BaseButton variant="ghost" @click="onLogout">Выйти</BaseButton>
    </div>

    <template v-if="!isLoading">
      <PetCard v-if="candidates.length > 0" :pet="candidates[0]" />
      <div
        v-else
        class="flex min-h-[420px] flex-1 items-center justify-center rounded-2xl bg-surface-soft text-center text-sm text-ink-faint"
      >
        Пока новых анкет рядом нет — загляните позже
      </div>

      <div class="flex justify-center gap-4 py-2">
        <button
          class="flex h-14 w-14 items-center justify-center rounded-full border border-clay-soft text-lg text-clay"
          @click="onSwipe('dislike')"
        >
          ✕
        </button>
        <button
          class="flex h-14 w-14 items-center justify-center rounded-full border border-hairline text-lg text-accent"
          @click="onSwipe('super_like')"
        >
          ★
        </button>
        <button
          class="flex h-[58px] w-[58px] items-center justify-center rounded-full bg-teal text-lg text-white"
          @click="onSwipe('like')"
        >
          ♥
        </button>
      </div>
    </template>

    <MatchModal :open="currentMatch !== null" @continue="dismissMatch" @chat="goToChat" />
  </div>
</template>
