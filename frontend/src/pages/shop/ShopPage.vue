<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { PackageOpen, ShoppingCart, Store } from 'lucide-vue-next'
import BottomNav from '@/widgets/BottomNav.vue'
import MarketTabs from '@/widgets/MarketTabs.vue'
import * as shopApi from '@/entities/shop/api'
import { useCartStore } from '@/entities/shop/model'
import type { ShopCategory, ShopProduct } from '@/entities/shop/types'
import { formatPrice } from '@/shared/lib/money'
import BaseChip from '@/shared/ui/components/BaseChip.vue'
import BaseCounter from '@/shared/ui/components/BaseCounter.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseSearchInput from '@/shared/ui/components/BaseSearchInput.vue'
import BaseSkeleton from '@/shared/ui/components/BaseSkeleton.vue'

const router = useRouter()
const cartStore = useCartStore()

const categories = ref<ShopCategory[]>([])
const products = ref<ShopProduct[]>([])
const activeCategory = ref<string | null>(null)
const query = ref('')
const isLoading = ref(true)

async function load(): Promise<void> {
  isLoading.value = true
  try {
    products.value = (
      await shopApi.listProducts({
        category: activeCategory.value ?? undefined,
        q: query.value.trim() || undefined,
      })
    ).data
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  categories.value = (await shopApi.listCategories()).data
  await Promise.all([load(), cartStore.fetch()])
})

// Поиск дёргает сервер на каждое изменение строки — список товаров небольшой,
// отдельный дебаунс пока не нужен.
watch(query, load)

function toggleCategory(slug: string): void {
  activeCategory.value = activeCategory.value === slug ? null : slug
  load()
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-none lg:px-8"
  >
    <div class="flex items-center justify-between gap-2 px-2">
      <h1 class="font-display text-xl font-bold text-ink">Маркет</h1>
      <div class="flex items-center gap-3">
        <button
          class="text-xs font-bold text-accent-text"
          @click="router.push({ name: 'shop-orders' })"
        >
          Заказы
        </button>
        <button
          class="text-xs font-bold text-accent-text"
          @click="router.push({ name: 'shop-my-products' })"
        >
          Мои товары
        </button>
        <button
          class="relative grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
          aria-label="Корзина"
          @click="router.push({ name: 'shop-cart' })"
        >
          <ShoppingCart class="size-5" />
          <BaseCounter
            v-if="cartStore.count > 0"
            class="absolute -top-0.5 -right-0.5 !h-4 !min-w-4 !px-1 !text-[10px]"
            :value="cartStore.count"
            :limit="9"
          />
        </button>
      </div>
    </div>

    <div class="px-2"><MarketTabs /></div>

    <!-- На десктопе фильтры уезжают в левую колонку и становятся вертикальным списком:
    горизонтальная лента чипов на широком экране прокручивается вслепую. -->
    <div class="flex-1 px-2 pb-4 lg:grid lg:grid-cols-[220px_1fr] lg:items-start lg:gap-6">
      <div class="flex flex-col gap-3 lg:sticky lg:top-6">
        <BaseSearchInput v-model="query" placeholder="Корм, игрушка, лежанка" />

        <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
          <BaseChip
            v-for="category in categories"
            :key="category.id"
            interactive
            size="md"
            class="lg:justify-start"
            :tone="activeCategory === category.slug ? 'accent' : 'outline'"
            @click="toggleCategory(category.slug)"
          >
            {{ category.name }}
          </BaseChip>
        </div>
      </div>

      <div>
        <div v-if="isLoading" class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
          <div v-for="cell in 4" :key="cell" class="flex flex-col gap-2">
            <BaseSkeleton variant="block" width="full" height="120px" />
            <BaseSkeleton width="80%" />
            <BaseSkeleton width="50%" />
          </div>
        </div>

        <div v-else-if="products.length === 0">
          <BaseEmptyState
            tone="gold"
            title="Здесь пока пусто"
            description="Попробуйте другую категорию или загляните позже — продавцы добавляют товары каждый день."
          >
            <template #icon><PackageOpen class="size-8" /></template>
          </BaseEmptyState>
        </div>

        <div v-else class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
          <button
            v-for="product in products"
            :key="product.id"
            type="button"
            class="flex flex-col overflow-hidden rounded-card border border-hairline bg-surface text-left transition-colors hover:border-accent"
            @click="router.push({ name: 'shop-product', params: { id: product.id } })"
          >
            <span class="grid h-[120px] w-full place-items-center bg-surface-soft">
              <img
                v-if="product.photo_url"
                :src="product.photo_url"
                :alt="product.title"
                class="size-full object-cover"
              />
              <Store v-else class="size-8 text-ink-faint" aria-hidden="true" />
            </span>
            <span class="flex flex-1 flex-col gap-1 p-3">
              <span class="line-clamp-2 text-[13px] font-semibold text-ink">{{
                product.title
              }}</span>
              <span class="mt-auto font-display text-[15px] font-bold text-ink">
                {{ formatPrice(product.price_amount, product.currency) }}
              </span>
            </span>
          </button>
        </div>
      </div>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
