<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronLeft, Receipt } from 'lucide-vue-next'
import * as shopApi from '@/entities/shop/api'
import type { ShopOrder, ShopOrderStatus } from '@/entities/shop/types'
import { formatPrice } from '@/shared/lib/money'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseSegmented from '@/shared/ui/components/BaseSegmented.vue'

const router = useRouter()

const role = ref<'buyer' | 'seller'>('buyer')
const orders = ref<ShopOrder[]>([])
const isLoading = ref(true)

const statusLabels: Record<ShopOrderStatus, string> = {
  pending_payment: 'Ожидает оплаты',
  paid_escrow: 'На удержании',
  shipped: 'В доставке',
  completed: 'Завершён',
  disputed: 'Спор',
  refunded: 'Возврат',
  cancelled: 'Отменён',
}

const statusTones: Record<ShopOrderStatus, 'gold' | 'info' | 'teal' | 'danger' | 'neutral'> = {
  pending_payment: 'gold',
  paid_escrow: 'info',
  shipped: 'info',
  completed: 'teal',
  disputed: 'danger',
  refunded: 'danger',
  cancelled: 'neutral',
}

async function load(): Promise<void> {
  isLoading.value = true
  try {
    orders.value = (await shopApi.listOrders(role.value)).data
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
watch(role, load)
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 pb-8 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.push({ name: 'shop' })"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Заказы</h1>
    </div>

    <div class="px-2">
      <BaseSegmented
        v-model="role"
        aria-label="Роль в заказах"
        :options="[
          { value: 'buyer', label: 'Я покупаю' },
          { value: 'seller', label: 'Я продаю' },
        ]"
      />
    </div>

    <div v-if="!isLoading && orders.length === 0" class="px-2">
      <BaseEmptyState
        tone="neutral"
        title="Заказов пока нет"
        :description="
          role === 'buyer'
            ? 'Оформленные заказы появятся здесь.'
            : 'Здесь появятся заказы на ваши товары.'
        "
      >
        <template #icon><Receipt class="size-8" /></template>
      </BaseEmptyState>
    </div>

    <div v-else class="flex flex-col gap-2.5 px-2">
      <button
        v-for="order in orders"
        :key="order.id"
        type="button"
        class="flex flex-col gap-2 rounded-card border border-hairline bg-surface p-4 text-left transition-colors hover:border-accent"
        @click="router.push({ name: 'shop-order-detail', params: { id: order.id } })"
      >
        <div class="flex items-center justify-between gap-3">
          <span class="font-display text-lg font-bold text-ink">
            {{ formatPrice(order.amount, order.currency) }}
          </span>
          <BaseBadge :tone="statusTones[order.status]">{{ statusLabels[order.status] }}</BaseBadge>
        </div>
        <p class="line-clamp-1 text-xs text-ink-faint">
          {{ order.items.map((item) => item.title).join(', ') }}
        </p>
        <p class="text-xs text-ink-faint">{{ order.delivery_label }}</p>
      </button>
    </div>
  </div>
</template>
