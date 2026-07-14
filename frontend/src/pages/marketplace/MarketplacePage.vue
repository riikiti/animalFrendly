<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import { useCatalogStore } from '@/entities/catalog/model'
import * as marketplaceApi from '@/entities/marketplace/api'
import type { Listing } from '@/entities/marketplace/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const catalogStore = useCatalogStore()

const listings = ref<Listing[]>([])
const isLoading = ref(true)
const purchasingId = ref<string | null>(null)
const error = ref('')

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()
  const response = await marketplaceApi.listListings()
  listings.value = response.data
  isLoading.value = false
})

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

function formatPrice(minorUnits: number, currency: string): string {
  const amount = (minorUnits / 100).toLocaleString('ru-RU')
  return `${amount} ${currency === 'RUB' ? '₽' : currency}`
}

async function purchase(listingId: string): Promise<void> {
  error.value = ''
  purchasingId.value = listingId

  try {
    const response = await marketplaceApi.purchaseListing(listingId)
    window.location.href = response.data.confirmation_url
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
    purchasingId.value = null
  }
}

const hasListings = computed(() => listings.value.length > 0)
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6">
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Маркет</span>
      <div class="flex gap-3">
        <button class="text-xs font-semibold text-teal" @click="router.push({ name: 'my-orders' })">
          Мои заказы
        </button>
        <button
          class="text-xs font-semibold text-teal"
          @click="router.push({ name: 'my-listings' })"
        >
          Мои листинги
        </button>
      </div>
    </div>

    <p v-if="error" class="px-2 text-xs text-danger">{{ error }}</p>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="!hasListings"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока нет питомцев на продажу
      </p>

      <div
        v-for="listing in listings"
        :key="listing.id"
        class="card flex flex-col gap-2 rounded-2xl border border-hairline p-3"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-13 w-13 shrink-0 items-center justify-center rounded-xl bg-teal-soft text-lg font-semibold text-teal"
          >
            {{ (listing.pet?.name ?? '?').charAt(0) }}
          </div>
          <div class="flex-1">
            <p class="text-sm font-semibold text-ink">{{ listing.pet?.name ?? 'Питомец' }}</p>
            <p class="text-xs text-ink-faint">
              {{ listing.pet ? speciesName(listing.pet.species_id) : '' }}
              <span v-if="listing.pet?.is_vaccinated"> · привит</span>
            </p>
          </div>
          <span
            class="chip rounded-full bg-accent/20 px-2 py-1 text-xs font-semibold text-accent-ink"
          >
            {{ formatPrice(listing.price_amount, listing.currency) }}
          </span>
        </div>
        <p v-if="listing.pet?.description" class="text-xs text-ink-soft">
          {{ listing.pet.description }}
        </p>

        <BaseButton :disabled="purchasingId === listing.id" @click="purchase(listing.id)">
          {{ purchasingId === listing.id ? 'Переходим к оплате…' : 'Купить' }}
        </BaseButton>
      </div>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
