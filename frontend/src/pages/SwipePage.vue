<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import MatchModal from '@/widgets/MatchModal.vue'
import PetCard from '@/widgets/PetCard.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as matchApi from '@/entities/match/api'
import type { PetMatch } from '@/entities/match/types'
import { useNotificationStore } from '@/entities/notification/model'
import { usePetStore } from '@/entities/pet/model'
import type { Pet } from '@/entities/pet/types'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import PaywallSheet from '@/shared/ui/components/PaywallSheet.vue'
import { useStaff } from '@/shared/lib/useStaff'

const router = useRouter()
const petStore = usePetStore()
const catalogStore = useCatalogStore()
const userStore = useUserStore()
const notificationStore = useNotificationStore()
const { isStaff } = useStaff()

const myPet = ref<Pet | null>(null)
const candidates = ref<Pet[]>([])
const currentMatch = ref<PetMatch | null>(null)
const isLoading = ref(true)
const paywallOpen = ref(false)
const paywallMessage = ref('')
const isBoosting = ref(false)

// Пользователь может выйти (см. onLogout) прежде, чем эта цепочка успеет завершиться —
// тогда токен уже очищен, и последующие запросы закономерно получат 401. Не считаем это
// необработанной ошибкой: страница уже покидается, реагировать не на что.
let isMounted = true
let unreadPollTimer: ReturnType<typeof setInterval> | null = null

onUnmounted(() => {
  isMounted = false
  if (unreadPollTimer) clearInterval(unreadPollTimer)
})

onMounted(async () => {
  try {
    await catalogStore.ensureSpeciesLoaded()
    await petStore.fetchMyPets()
    if (!isMounted) return

    if (petStore.myPets.length === 0) {
      await router.push({ name: 'create-pet' })
      return
    }

    myPet.value = petStore.myPets[0]
    await loadCandidates()
    isLoading.value = false

    await notificationStore.refreshUnreadCount()
    unreadPollTimer = setInterval(() => notificationStore.refreshUnreadCount(), 30000)
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

  try {
    const result = await matchApi.swipe(myPet.value.id, target.id, action)
    candidates.value = candidates.value.slice(1)

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
  }
}

async function onBoost(): Promise<void> {
  if (!myPet.value || isBoosting.value) return
  isBoosting.value = true

  try {
    await matchApi.boostPet(myPet.value.id)
  } catch (e) {
    if (e instanceof ApiError && e.status === 402) {
      paywallMessage.value = e.message
      paywallOpen.value = true
    } else {
      throw e
    }
  } finally {
    isBoosting.value = false
  }
}

function closePaywall(): void {
  paywallOpen.value = false
}

async function goToSubscriptionPlans(): Promise<void> {
  paywallOpen.value = false
  await router.push({ name: 'subscription-plans' })
}

function dismissMatch(): void {
  currentMatch.value = null
}

async function goToChat(): Promise<void> {
  if (!currentMatch.value) return
  await router.push({ name: 'chat', params: { kind: 'match', id: currentMatch.value.id } })
}

async function onLogout(): Promise<void> {
  await userStore.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6">
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">AnimalFriendly</span>
      <div class="flex items-center gap-3">
        <button
          v-if="isStaff"
          class="text-xs font-semibold text-danger"
          @click="router.push({ name: 'admin-dashboard' })"
        >
          Админ
        </button>
        <button
          class="text-xs font-semibold text-teal"
          @click="router.push({ name: 'search-pets' })"
        >
          🔍 Искать
        </button>
        <button
          class="text-lg text-ink-soft"
          title="Адрес"
          @click="router.push({ name: 'profile-settings' })"
        >
          📍
        </button>
        <button
          class="text-xs font-semibold text-teal"
          @click="router.push({ name: 'subscription-status' })"
        >
          Тариф
        </button>
        <button
          class="relative text-lg text-ink-soft"
          @click="router.push({ name: 'notifications' })"
        >
          🔔
          <span
            v-if="notificationStore.unreadCount > 0"
            class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-accent text-[10px] font-semibold text-accent-ink"
          >
            {{ notificationStore.unreadCount > 9 ? '9+' : notificationStore.unreadCount }}
          </span>
        </button>
        <BaseButton variant="ghost" @click="onLogout">Выйти</BaseButton>
      </div>
    </div>

    <template v-if="!isLoading">
      <PetCard v-if="candidates.length > 0" :pet="candidates[0]" />
      <div
        v-else
        class="flex min-h-[420px] flex-1 items-center justify-center rounded-2xl bg-surface-soft text-center text-sm text-ink-faint"
      >
        Пока новых анкет рядом нет — загляните позже
      </div>

      <button
        class="self-center text-xs font-semibold text-accent-ink disabled:opacity-50"
        :disabled="isBoosting"
        @click="onBoost"
      >
        {{ isBoosting ? 'Бустим…' : '✨ Забустить анкету' }}
      </button>

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
    <PaywallSheet
      :open="paywallOpen"
      :message="paywallMessage"
      @close="closePaywall"
      @upgrade="goToSubscriptionPlans"
    />

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
