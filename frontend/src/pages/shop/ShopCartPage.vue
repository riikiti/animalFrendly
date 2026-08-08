<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronLeft, ShoppingCart, Store, Trash2 } from 'lucide-vue-next'
import { useCartStore } from '@/entities/shop/model'
import { ApiError } from '@/shared/api/http'
import { formatPrice } from '@/shared/lib/money'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseIconButton from '@/shared/ui/components/BaseIconButton.vue'
import BaseMoneyRow from '@/shared/ui/components/BaseMoneyRow.vue'
import BaseStepper from '@/shared/ui/components/BaseStepper.vue'

const router = useRouter()
const cartStore = useCartStore()

const error = ref('')

onMounted(cartStore.fetch)

async function change(productId: string, quantity: number): Promise<void> {
  error.value = ''

  try {
    await cartStore.setQuantity(productId, quantity)
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не получилось изменить количество.'
    await cartStore.fetch()
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
      <h1 class="font-display text-xl font-bold text-ink">Корзина</h1>
    </div>

    <BaseAlert v-if="error" tone="error" class="mx-2">{{ error }}</BaseAlert>

    <div v-if="!cartStore.isLoading && cartStore.isEmpty" class="px-2">
      <BaseEmptyState
        tone="gold"
        title="Корзина пуста"
        description="Загляните в маркет — там корма, игрушки и всё остальное для питомца."
      >
        <template #icon><ShoppingCart class="size-8" /></template>
        <template #actions>
          <BaseButton @click="router.push({ name: 'shop' })">В маркет</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <div class="flex flex-col gap-2.5 px-2">
        <div
          v-for="line in cartStore.cart.items"
          :key="line.product.id"
          class="flex gap-3 rounded-card border border-hairline bg-surface p-3"
        >
          <span
            class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-surface-soft"
          >
            <img
              v-if="line.product.photo_url"
              :src="line.product.photo_url"
              :alt="line.product.title"
              class="size-full object-cover"
            />
            <Store v-else class="size-6 text-ink-faint" aria-hidden="true" />
          </span>

          <div class="flex min-w-0 flex-1 flex-col gap-2">
            <div class="flex items-start justify-between gap-2">
              <span class="line-clamp-2 text-[13px] font-semibold text-ink">{{
                line.product.title
              }}</span>
              <BaseIconButton
                label="Убрать из корзины"
                size="sm"
                tone="danger"
                @click="cartStore.remove(line.product.id)"
              >
                <Trash2 class="size-4" />
              </BaseIconButton>
            </div>

            <div class="flex items-center justify-between gap-2">
              <BaseStepper
                :model-value="line.quantity"
                aria-label="Количество"
                :min="1"
                :max="line.product.stock"
                @update:model-value="change(line.product.id, $event)"
              />
              <span class="font-display text-[15px] font-bold text-ink">
                {{ formatPrice(line.product.price_amount * line.quantity, line.product.currency) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-auto flex flex-col gap-3 px-2">
        <div class="rounded-card border border-hairline bg-surface p-4">
          <BaseMoneyRow
            label="Товары"
            :value="formatPrice(cartStore.cart.total_amount, cartStore.cart.currency)"
            variant="total"
          />
          <p class="mt-2 text-xs text-ink-faint">Доставку посчитаем на следующем шаге.</p>
        </div>

        <BaseButton size="lg" block @click="router.push({ name: 'shop-checkout' })">
          Оформить заказ
        </BaseButton>
      </div>
    </template>
  </div>
</template>
