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
import BaseChip from '@/shared/ui/components/BaseChip.vue'
import BaseCounter from '@/shared/ui/components/BaseCounter.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseIconButton from '@/shared/ui/components/BaseIconButton.vue'
import PaywallSheet from '@/shared/ui/components/PaywallSheet.vue'
import { useStaff } from '@/shared/lib/useStaff'
import {
  Bell,
  Gem,
  Heart,
  MessageCircle,
  PawPrint,
  Search,
  Shield,
  Sparkles,
  Star,
  User,
  X,
} from 'lucide-vue-next'

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
    <div class="flex items-center justify-between gap-2 px-2">
      <span class="inline-flex shrink-0 items-center gap-2">
        <span class="grid size-7 place-items-center rounded-[10px] bg-accent text-accent-ink">
          <PawPrint class="size-4" aria-hidden="true" />
        </span>
        <span class="font-display text-base font-bold text-ink">AnimalFriendly</span>
      </span>
      <!-- min-w-0 позволяет этому flex-item'у сжаться меньше суммарной ширины иконок,
      иначе overflow-x-auto не сработал бы, а сам ряд просто раздвигал бы родителя — на узких
      экранах (320-360px) шести-семи иконок уже не хватает по ширине без прокрутки. -->
      <div class="flex min-w-0 items-center gap-0.5 overflow-x-auto">
        <button
          v-if="isStaff"
          class="grid size-9 shrink-0 place-items-center rounded-full text-danger transition-colors hover:bg-surface-soft"
          aria-label="Админ"
          title="Админ"
          @click="router.push({ name: 'admin-dashboard' })"
        >
          <Shield class="size-5" />
        </button>
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Искать"
          title="Искать"
          @click="router.push({ name: 'search-pets' })"
        >
          <Search class="size-5" />
        </button>
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Чаты"
          title="Чаты"
          @click="router.push({ name: 'conversations-list' })"
        >
          <MessageCircle class="size-5" />
        </button>
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Лайки"
          title="Лайки"
          @click="router.push({ name: 'pending-likes' })"
        >
          <Heart class="size-5" />
        </button>
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Профиль"
          title="Профиль"
          @click="router.push({ name: 'profile' })"
        >
          <User class="size-5" />
        </button>
        <button
          class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Тариф"
          title="Тариф"
          @click="router.push({ name: 'subscription-status' })"
        >
          <Gem class="size-5" />
        </button>
        <button
          class="relative grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Уведомления"
          title="Уведомления"
          @click="router.push({ name: 'notifications' })"
        >
          <Bell class="size-5" />
          <BaseCounter
            v-if="notificationStore.unreadCount > 0"
            class="absolute -top-0.5 -right-0.5 !h-4 !min-w-4 !px-1 !text-[10px]"
            :value="notificationStore.unreadCount"
            :limit="9"
          />
        </button>
      </div>
    </div>

    <div v-if="petStore.myPets.length > 1" class="flex flex-wrap gap-2 px-2">
      <BaseChip
        v-for="pet in petStore.myPets"
        :key="pet.id"
        interactive
        :tone="myPet?.id === pet.id ? 'accent' : 'neutral'"
        @click="switchPet(pet)"
      >
        {{ pet.name }}
      </BaseChip>
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
        <BaseEmptyState
          v-else
          class="min-h-[420px] flex-1"
          title="Пока новых анкет рядом нет"
          description="Загляните позже — мы покажем питомцев, которые появятся поблизости."
        >
          <template #icon><PawPrint class="size-8" /></template>
        </BaseEmptyState>

        <button
          class="inline-flex items-center gap-1.5 self-center text-[13px] font-bold text-accent-text disabled:opacity-50"
          :disabled="isBoosting"
          @click="onBoost"
        >
          <Sparkles class="size-4" aria-hidden="true" />
          {{ isBoosting ? 'Бустим…' : 'Забустить анкету' }}
        </button>

        <div class="flex items-center justify-center gap-4 py-2">
          <BaseIconButton label="Пропустить" elevated @click="onSwipe('dislike')">
            <X class="size-6" />
          </BaseIconButton>
          <BaseIconButton label="Суперлайк" tone="success" elevated @click="onSwipe('super_like')">
            <Star class="size-5 fill-current" />
          </BaseIconButton>
          <BaseIconButton label="Лайк" tone="active" size="lg" elevated @click="onSwipe('like')">
            <Heart class="size-7 fill-current" />
          </BaseIconButton>
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
