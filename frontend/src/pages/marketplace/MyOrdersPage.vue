<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import * as marketplaceApi from '@/entities/marketplace/api'
import type { Order, OrderRole } from '@/entities/marketplace/types'

const router = useRouter()

const role = ref<OrderRole>('buyer')
const orders = ref<Order[]>([])
const isLoading = ref(true)

const statusLabels: Record<string, string> = {
  pending_payment: 'Ожидает оплаты',
  paid_escrow: 'Оплачено, на удержании',
  completed: 'Завершена',
  disputed: 'Спор',
  refunded: 'Возврат',
  cancelled: 'Отменена',
}

async function load(): Promise<void> {
  isLoading.value = true
  const response = await marketplaceApi.listMyOrders(role.value)
  orders.value = response.data
  isLoading.value = false
}

watch(role, load, { immediate: true })

function formatPrice(minorUnits: number, currency: string): string {
  const amount = (minorUnits / 100).toLocaleString('ru-RU')
  return `${amount} ${currency === 'RUB' ? '₽' : currency}`
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Мои заказы</span>
    </div>

    <div class="flex gap-2 px-2">
      <button
        class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
        :class="role === 'buyer' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
        @click="role = 'buyer'"
      >
        Покупки
      </button>
      <button
        class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
        :class="role === 'seller' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
        @click="role = 'seller'"
      >
        Продажи
      </button>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="orders.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Пока ничего нет
      </p>

      <button
        v-for="order in orders"
        :key="order.id"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-3 text-left"
        @click="router.push({ name: 'order-detail', params: { id: order.id } })"
      >
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold text-ink">{{
            formatPrice(order.amount, order.currency)
          }}</span>
          <span class="rounded-full bg-surface-soft px-2 py-1 text-xs font-semibold text-ink-soft">
            {{ statusLabels[order.status] }}
          </span>
        </div>
      </button>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
