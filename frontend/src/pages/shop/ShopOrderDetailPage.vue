<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronLeft, MapPin } from 'lucide-vue-next'
import * as shopApi from '@/entities/shop/api'
import type { ShopOrder, ShopOrderStatus } from '@/entities/shop/types'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'
import { formatPrice } from '@/shared/lib/money'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseMoneyRow from '@/shared/ui/components/BaseMoneyRow.vue'
import BaseStatusStep from '@/shared/ui/components/BaseStatusStep.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const orderId = String(route.params.id)
const order = ref<ShopOrder | null>(null)
const isLoading = ref(true)
const error = ref('')

const statusLabels: Record<ShopOrderStatus, string> = {
  pending_payment: 'Ожидает оплаты',
  paid_escrow: 'Оплачен, деньги на удержании',
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

const isBuyer = computed(() => order.value?.buyer_id === userStore.currentUser?.id)
const isSeller = computed(() => order.value?.seller_id === userStore.currentUser?.id)

const hasConfirmed = computed(() =>
  isBuyer.value
    ? order.value?.buyer_confirmed_at !== null
    : order.value?.seller_confirmed_at !== null,
)

const steps = computed<{ title: string; meta?: string; state: 'done' | 'current' | 'upcoming' }[]>(
  () => {
    const status = order.value?.status
    const paid = status !== undefined && !['pending_payment', 'cancelled'].includes(status)
    const shipped = status === 'shipped' || status === 'completed'
    const done = status === 'completed'

    return [
      { title: 'Заказ оформлен', state: 'done' },
      {
        title: 'Оплачено · деньги на эскроу',
        meta: paid ? 'Площадка удерживает сумму до подтверждения' : 'Ждём оплату',
        state: paid ? 'done' : 'current',
      },
      {
        title: 'Передан в доставку',
        state: shipped ? 'done' : paid ? 'current' : 'upcoming',
      },
      {
        title: 'Подтверждение сторон',
        meta:
          [
            order.value?.buyer_confirmed_at ? 'покупатель ✓' : null,
            order.value?.seller_confirmed_at ? 'продавец ✓' : null,
          ]
            .filter(Boolean)
            .join(' · ') || undefined,
        state: done ? 'done' : shipped ? 'current' : 'upcoming',
      },
    ]
  },
)

let pollTimer: ReturnType<typeof setInterval> | null = null

async function load(): Promise<void> {
  order.value = (await shopApi.getOrder(orderId)).data

  if (order.value.status !== 'pending_payment' && pollTimer !== null) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(async () => {
  await load()
  isLoading.value = false

  // Оплата подтверждается вебхуком, поэтому статус ждём опросом — как на сделке маркетплейса.
  if (order.value?.status === 'pending_payment') {
    pollTimer = setInterval(load, 3000)
  }
})

onUnmounted(() => {
  if (pollTimer !== null) clearInterval(pollTimer)
})

async function run(action: () => Promise<{ data: ShopOrder }>): Promise<void> {
  error.value = ''

  try {
    order.value = (await action()).data
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 pb-8 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.push({ name: 'shop-orders' })"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Заказ</h1>
    </div>

    <template v-if="!isLoading && order">
      <div class="flex flex-col gap-3 rounded-card border border-hairline bg-surface p-4 mx-2">
        <div class="flex items-center justify-between gap-3">
          <span class="font-display text-2xl font-bold text-ink">
            {{ formatPrice(order.amount, order.currency) }}
          </span>
          <BaseBadge :tone="statusTones[order.status]">{{ statusLabels[order.status] }}</BaseBadge>
        </div>

        <div class="flex flex-col gap-2 border-t border-hairline pt-3">
          <BaseMoneyRow
            v-for="item in order.items"
            :key="item.product_id"
            :label="`${item.title} × ${item.quantity}`"
            :value="formatPrice(item.price_amount * item.quantity, order.currency)"
          />
          <BaseMoneyRow
            :label="order.delivery_label"
            :value="
              order.delivery_amount === 0
                ? 'бесплатно'
                : formatPrice(order.delivery_amount, order.currency)
            "
          />
          <BaseMoneyRow
            v-if="isSeller && order.commission_amount !== null"
            label="Комиссия площадки"
            :value="`−${formatPrice(order.commission_amount, order.currency)}`"
            negative
          />
          <BaseMoneyRow
            v-if="isSeller && order.payout_amount !== null"
            label="Вы получите"
            :value="formatPrice(order.payout_amount, order.currency)"
            variant="total"
          />
        </div>

        <p
          v-if="order.status === 'paid_escrow' && order.escrow_hold_until"
          class="text-xs text-ink-faint"
        >
          Деньги удерживаются до
          {{ new Date(order.escrow_hold_until).toLocaleDateString('ru-RU') }}
        </p>
      </div>

      <div
        v-if="order.delivery_address"
        class="mx-2 flex items-center gap-2 rounded-2xl bg-surface-soft px-3 py-2.5"
      >
        <MapPin class="size-3.5 shrink-0 text-ink-soft" aria-hidden="true" />
        <span class="truncate text-xs text-ink-soft">{{ order.delivery_address }}</span>
      </div>

      <div class="mx-2 flex flex-col rounded-card border border-hairline bg-surface p-4">
        <h2 class="mb-3 text-sm font-semibold text-ink-soft">Как идёт заказ</h2>
        <BaseStatusStep
          v-for="(step, index) in steps"
          :key="step.title"
          :title="step.title"
          :meta="step.meta"
          :state="step.state"
          :last="index === steps.length - 1"
        />
      </div>

      <BaseAlert v-if="error" tone="error" class="mx-2">{{ error }}</BaseAlert>

      <div class="mt-auto flex flex-col gap-2.5 px-2">
        <BaseButton
          v-if="order.status === 'pending_payment' && isBuyer"
          variant="ghost"
          block
          @click="run(() => shopApi.cancelOrder(orderId))"
        >
          Отменить заказ
        </BaseButton>

        <BaseButton
          v-if="order.status === 'paid_escrow' && isSeller"
          size="lg"
          block
          @click="run(() => shopApi.shipOrder(orderId))"
        >
          Передал в доставку
        </BaseButton>

        <template v-if="['paid_escrow', 'shipped'].includes(order.status)">
          <BaseButton
            v-if="!hasConfirmed"
            size="lg"
            block
            @click="run(() => shopApi.confirmOrder(orderId))"
          >
            Подтвердить получение
          </BaseButton>
          <BaseAlert v-else tone="success">Вы подтвердили, ждём вторую сторону</BaseAlert>

          <BaseButton variant="ghost" block @click="run(() => shopApi.disputeOrder(orderId))">
            Открыть спор
          </BaseButton>
        </template>
      </div>
    </template>
  </div>
</template>
