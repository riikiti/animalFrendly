<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalogStore } from '@/entities/catalog/model'
import * as conversationApi from '@/entities/conversation/api'
import * as marketplaceApi from '@/entities/marketplace/api'
import type { Listing } from '@/entities/marketplace/types'
import { useUserStore } from '@/entities/user/model'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const catalogStore = useCatalogStore()
const userStore = useUserStore()

const listings = ref<Listing[]>([])
const isLoading = ref(true)
const notFound = ref(false)
const isContacting = ref(false)

const sellerListings = computed(() =>
  listings.value.filter((listing) => listing.seller_id === String(route.params.id)),
)
const seller = computed(() => sellerListings.value[0] ?? null)
const isSelf = computed(() => seller.value?.seller_id === userStore.currentUser?.id)

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()

  try {
    const response = await marketplaceApi.listListings()
    listings.value = response.data

    if (!listings.value.some((listing) => listing.seller_id === String(route.params.id))) {
      notFound.value = true
    }
  } finally {
    isLoading.value = false
  }
})

function speciesName(speciesId: number): string {
  return catalogStore.speciesName(speciesId)
}

function formatPrice(minorUnits: number, currency: string): string {
  const amount = (minorUnits / 100).toLocaleString('ru-RU')
  return `${amount} ${currency === 'RUB' ? '₽' : currency}`
}

async function contactSeller(): Promise<void> {
  if (!seller.value || isContacting.value) return
  isContacting.value = true

  try {
    const response = await conversationApi.createDirectConversation(seller.value.seller_id)
    await router.push({ name: 'chat', params: { kind: 'direct', id: response.data.id } })
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
      <span class="font-display text-lg text-ink">Продавец</span>
    </div>

    <p v-if="notFound" class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint">
      Продавец не найден
    </p>

    <template v-else-if="!isLoading && seller">
      <div class="flex items-center gap-3 px-2">
        <div
          class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-teal-soft text-lg font-semibold text-teal"
        >
          <img
            v-if="seller.seller_avatar_url"
            :src="seller.seller_avatar_url"
            class="h-full w-full object-cover"
            alt=""
          />
          <span v-else>{{ (seller.seller_name ?? '?').charAt(0) }}</span>
        </div>
        <div class="flex flex-col">
          <span class="font-display text-xl text-ink">{{ seller.seller_name ?? 'Продавец' }}</span>
          <span
            class="text-xs font-semibold"
            :class="seller.seller_verified ? 'text-teal' : 'text-ink-faint'"
          >
            {{
              seller.seller_verified ? '✓ Подтверждённый заводчик' : 'Заводчик не подтвердил данные'
            }}
          </span>
        </div>
      </div>

      <div v-if="!isSelf" class="px-2">
        <BaseButton :disabled="isContacting" @click="contactSeller">
          {{ isContacting ? 'Открываем чат…' : 'Написать' }}
        </BaseButton>
      </div>

      <div class="flex flex-col gap-2 border-t border-hairline px-2 pt-4">
        <span class="font-display text-base text-ink">Объявления</span>
        <div
          v-for="listing in sellerListings"
          :key="listing.id"
          class="flex flex-col gap-1 rounded-2xl border border-hairline p-3"
        >
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-ink">{{ listing.pet?.name ?? 'Питомец' }}</p>
            <span
              class="chip rounded-full bg-accent/20 px-2 py-1 text-xs font-semibold text-accent-ink"
            >
              {{ formatPrice(listing.price_amount, listing.currency) }}
            </span>
          </div>
          <p class="text-xs text-ink-faint">
            {{ listing.pet ? speciesName(listing.pet.species_id) : '' }}
          </p>
          <p v-if="listing.pet?.parent_name" class="text-xs text-ink-faint">
            Щенок от {{ listing.pet.parent_name }}
          </p>
        </div>
      </div>
    </template>
  </div>
</template>
