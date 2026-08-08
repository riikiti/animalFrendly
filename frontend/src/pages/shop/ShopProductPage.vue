<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronLeft, ShieldCheck, Store } from 'lucide-vue-next'
import * as shopApi from '@/entities/shop/api'
import { useCartStore } from '@/entities/shop/model'
import type { ShopProduct } from '@/entities/shop/types'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'
import { formatPrice } from '@/shared/lib/money'
import { pushToast } from '@/shared/lib/toast'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseStepper from '@/shared/ui/components/BaseStepper.vue'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const userStore = useUserStore()

const product = ref<ShopProduct | null>(null)
const quantity = ref(1)
const isLoading = ref(true)
const isAdding = ref(false)
const error = ref('')

const isOwn = computed(() => product.value?.seller_id === userStore.currentUser?.id)

onMounted(async () => {
  product.value = (await shopApi.getProduct(String(route.params.id))).data
  isLoading.value = false
})

async function addToCart(): Promise<void> {
  if (!product.value) return
  error.value = ''
  isAdding.value = true

  try {
    await cartStore.add(product.value.id, quantity.value)
    pushToast({
      tone: 'success',
      title: 'Добавлено в корзину',
      description: product.value.title,
      actionLabel: 'Открыть',
      onAction: () => router.push({ name: 'shop-cart' }),
    })
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не получилось добавить. Попробуйте ещё раз.'
  } finally {
    isAdding.value = false
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 pb-8 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.back()"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Товар</h1>
    </div>

    <template v-if="!isLoading && product">
      <div class="grid h-[220px] place-items-center overflow-hidden rounded-card bg-surface-soft">
        <img
          v-if="product.photo_url"
          :src="product.photo_url"
          :alt="product.title"
          class="size-full object-cover"
        />
        <Store v-else class="size-12 text-ink-faint" aria-hidden="true" />
      </div>

      <div class="flex flex-col gap-2 px-2">
        <h2 class="font-display text-xl font-bold text-ink">{{ product.title }}</h2>
        <span class="font-display text-2xl font-bold text-ink">
          {{ formatPrice(product.price_amount, product.currency) }}
        </span>
        <div class="flex flex-wrap gap-2">
          <BaseBadge :tone="product.stock > 0 ? 'teal' : 'neutral'">
            {{ product.stock > 0 ? `В наличии: ${product.stock} шт.` : 'Нет в наличии' }}
          </BaseBadge>
        </div>
        <p v-if="product.description" class="text-sm leading-relaxed text-ink-soft">
          {{ product.description }}
        </p>
      </div>

      <div class="flex items-start gap-3 rounded-card bg-accent-soft p-3.5">
        <ShieldCheck class="mt-0.5 size-5 shrink-0 text-accent-text" aria-hidden="true" />
        <p class="text-[12.5px] leading-relaxed text-accent-text">
          Деньги держит площадка, пока вы не подтвердите, что заказ получен.
        </p>
      </div>

      <BaseAlert v-if="error" tone="error" class="mx-2">{{ error }}</BaseAlert>

      <div v-if="!isOwn" class="mt-auto flex items-center gap-3 px-2">
        <BaseStepper v-model="quantity" aria-label="Количество" :min="1" :max="product.stock" />
        <BaseButton
          size="lg"
          class="flex-1"
          :loading="isAdding"
          :disabled="product.stock === 0"
          @click="addToCart"
        >
          В корзину
        </BaseButton>
      </div>
      <p v-else class="mt-auto px-2 text-center text-[13px] text-ink-faint">Это ваш товар</p>
    </template>
  </div>
</template>
