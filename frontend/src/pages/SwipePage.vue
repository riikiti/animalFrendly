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
import { ApiError } from '@/shared/api/http'
import PaywallSheet from '@/shared/ui/components/PaywallSheet.vue'
import { useStaff } from '@/shared/lib/useStaff'

const router = useRouter()
const petStore = usePetStore()
const catalogStore = useCatalogStore()
const notificationStore = useNotificationStore()
const { isStaff } = useStaff()

const myPet = ref<Pet | null>(null)
const candidates = ref<Pet[]>([])
const currentMatch = ref<PetMatch | null>(null)
const isLoading = ref(true)
const paywallOpen = ref(false)
const paywallMessage = ref('')
const isBoosting = ref(false)

// Пользователь может выйти со страницы профиля прежде, чем эта цепочка успеет
// завершиться — тогда токен уже очищен, и последующие запросы закономерно получат 401.
// Не считаем это необработанной ошибкой: страница уже покидается, реагировать не на что.
let isMounted = true

onUnmounted(() => {
  isMounted = false
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

    // Разовая гидратация — дальнейшие уведомления приходят живьём через App.vue
    // (подписка на приватный канал user.{id}, см. shared/lib/echo.ts).
    await notificationStore.refreshUnreadCount()
  } catch (error) {
    if (isMounted) throw error
  }
})

async function loadCandidates(): Promise<void> {
  if (!myPet.value) return
  const response = await matchApi.listCandidates(myPet.value.id)
  candidates.value = response.data
}

// Бесплатно доступна одна анкета, по подписке — сколько угодно (см. CreatePetHandler);
// ListCandidates уже параметризован petId, поэтому переключение — это просто смена активной
// анкеты и повторная загрузка ленты, без изменений на бэкенде.
async function switchPet(pet: Pet): Promise<void> {
  if (myPet.value?.id === pet.id) return
  myPet.value = pet
  isLoading.value = true
  await loadCandidates()
  isLoading.value = false
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
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">AnimalFriendly</span>
      <div class="flex items-center gap-1">
        <button
          v-if="isStaff"
          class="flex h-9 w-9 items-center justify-center rounded-full text-lg text-danger hover:bg-surface-soft"
          aria-label="Админ"
          title="Админ"
          @click="router.push({ name: 'admin-dashboard' })"
        >
          🛡️
        </button>
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full text-lg text-ink-soft hover:bg-surface-soft"
          aria-label="Искать"
          title="Искать"
          @click="router.push({ name: 'search-pets' })"
        >
          🔍
        </button>
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full text-lg text-ink-soft hover:bg-surface-soft"
          aria-label="Чаты"
          title="Чаты"
          @click="router.push({ name: 'conversations-list' })"
        >
          💬
        </button>
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full text-lg text-ink-soft hover:bg-surface-soft"
          aria-label="Лайки"
          title="Лайки"
          @click="router.push({ name: 'pending-likes' })"
        >
          ❤️
        </button>
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full text-lg text-ink-soft hover:bg-surface-soft"
          aria-label="Профиль"
          title="Профиль"
          @click="router.push({ name: 'profile' })"
        >
          👤
        </button>
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full text-lg text-ink-soft hover:bg-surface-soft"
          aria-label="Тариф"
          title="Тариф"
          @click="router.push({ name: 'subscription-status' })"
        >
          💎
        </button>
        <button
          class="relative flex h-9 w-9 items-center justify-center rounded-full text-lg text-ink-soft hover:bg-surface-soft"
          aria-label="Уведомления"
          title="Уведомления"
          @click="router.push({ name: 'notifications' })"
        >
          🔔
          <span
            v-if="notificationStore.unreadCount > 0"
            class="absolute right-0.5 top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-accent text-[10px] font-semibold text-accent-ink"
          >
            {{ notificationStore.unreadCount > 9 ? '9+' : notificationStore.unreadCount }}
          </span>
        </button>
      </div>
    </div>

    <div v-if="petStore.myPets.length > 1" class="flex flex-wrap gap-2 px-2">
      <button
        v-for="pet in petStore.myPets"
        :key="pet.id"
        type="button"
        class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
        :class="myPet?.id === pet.id ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
        @click="switchPet(pet)"
      >
        {{ pet.name }}
      </button>
    </div>

    <template v-if="!isLoading">
      <!-- Tinder-карточка остаётся компактной и по центру даже на широком десктопном
      экране — растягивать её до ширины страницы (см. лимиты выше) выглядело бы не по-Tinder'ски. -->
      <div class="mx-auto flex w-full max-w-sm flex-1 flex-col gap-4">
        <PetCard
          v-if="candidates.length > 0"
          :key="candidates[0].id"
          :pet="candidates[0]"
          @swipe="onSwipe"
        />
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
