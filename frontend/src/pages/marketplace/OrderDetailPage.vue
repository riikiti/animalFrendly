<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as marketplaceApi from '@/entities/marketplace/api'
import type { Order } from '@/entities/marketplace/types'
import * as moderationApi from '@/entities/moderation/api'
import { useUserStore } from '@/entities/user/model'
import { ChevronLeft, MapPin, Star } from 'lucide-vue-next'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseMoneyRow from '@/shared/ui/components/BaseMoneyRow.vue'
import BaseStatusStep from '@/shared/ui/components/BaseStatusStep.vue'
import BaseTextarea from '@/shared/ui/components/BaseTextarea.vue'
import { ApiError } from '@/shared/api/http'
import { formatPrice } from '@/shared/lib/money'
import { yandexRouteUrl } from '@/shared/lib/directions'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const orderId = route.params.id as string

const order = ref<Order | null>(null)
const isLoading = ref(true)
const error = ref('')
const showDisputeForm = ref(false)
const disputeReason = ref('')

const reviewRating = ref(5)
const reviewComment = ref('')
const reviewSubmitted = ref(false)
const reviewError = ref('')

const statusLabels: Record<string, string> = {
  pending_payment: 'Ожидает оплаты',
  paid_escrow: 'Оплачено, на удержании',
  completed: 'Завершена',
  disputed: 'Спор',
  refunded: 'Возврат',
  cancelled: 'Отменена',
}

const statusTones: Record<string, 'gold' | 'info' | 'teal' | 'danger' | 'neutral'> = {
  pending_payment: 'gold',
  paid_escrow: 'info',
  completed: 'teal',
  disputed: 'danger',
  refunded: 'danger',
  cancelled: 'neutral',
}

// Лента статусов эскроу: до подтверждения обеими сторонами деньги держит площадка.
const dealSteps = computed<
  { title: string; meta?: string; state: 'done' | 'current' | 'upcoming' }[]
>(() => {
  const status = order.value?.status
  const paid = status !== 'pending_payment' && status !== 'cancelled'
  const done = status === 'completed'
  const buyerOk = order.value?.buyer_confirmed_at !== null
  const sellerOk = order.value?.seller_confirmed_at !== null

  return [
    {
      title: 'Заказ оформлен',
      state: 'done',
    },
    {
      title: 'Оплачено · деньги на эскроу',
      meta: paid ? 'Площадка удерживает сумму до подтверждения' : 'Ждём оплату',
      state: paid ? 'done' : 'current',
    },
    {
      title: 'Подтверждение сторон',
      meta:
        [buyerOk ? 'покупатель ✓' : null, sellerOk ? 'продавец ✓' : null]
          .filter(Boolean)
          .join(' · ') || undefined,
      state: done ? 'done' : paid ? 'current' : 'upcoming',
    },
    {
      title: 'Выплата продавцу',
      state: done ? 'done' : 'upcoming',
    },
  ]
})

let pollTimer: ReturnType<typeof setInterval> | null = null
let isMounted = true

async function load(): Promise<void> {
  const response = await marketplaceApi.getOrder(orderId)
  order.value = response.data

  if (order.value.status !== 'pending_payment' && pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(async () => {
  try {
    await load()
    if (order.value?.status === 'pending_payment') {
      pollTimer = setInterval(load, 3000)
    }
  } catch (e) {
    if (isMounted) throw e
  } finally {
    if (isMounted) isLoading.value = false
  }
})

onUnmounted(() => {
  isMounted = false
  if (pollTimer) clearInterval(pollTimer)
})

const isBuyer = computed(() => order.value?.buyer_id === userStore.currentUser?.id)
const hasConfirmed = computed(() => {
  if (!order.value) return false
  return isBuyer.value
    ? order.value.buyer_confirmed_at !== null
    : order.value.seller_confirmed_at !== null
})

async function confirm(): Promise<void> {
  error.value = ''
  try {
    await marketplaceApi.confirmOrder(orderId)
    await load()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}

async function cancel(): Promise<void> {
  error.value = ''
  try {
    await marketplaceApi.cancelOrder(orderId)
    await load()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}

async function submitDispute(): Promise<void> {
  error.value = ''
  try {
    await marketplaceApi.openDispute(orderId, disputeReason.value)
    showDisputeForm.value = false
    await load()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}

async function submitReview(): Promise<void> {
  reviewError.value = ''
  try {
    await moderationApi.submitReview({
      order_id: orderId,
      rating: reviewRating.value,
      comment: reviewComment.value || null,
    })
    reviewSubmitted.value = true
  } catch (e) {
    reviewError.value =
      e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.push({ name: 'my-orders' })"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Заказ</h1>
    </div>

    <div v-if="!isLoading && order" class="flex flex-col gap-4 px-2">
      <div class="flex flex-col gap-3 rounded-card border border-hairline bg-surface p-4">
        <div class="flex items-center justify-between gap-3">
          <span class="font-display text-2xl font-bold text-ink">{{
            formatPrice(order.amount, order.currency)
          }}</span>
          <BaseBadge :tone="statusTones[order.status]">{{ statusLabels[order.status] }}</BaseBadge>
        </div>

        <div class="flex flex-col gap-2 border-t border-hairline pt-3">
          <BaseMoneyRow label="Сумма сделки" :value="formatPrice(order.amount, order.currency)" />
          <BaseMoneyRow
            v-if="order.commission_amount !== null"
            label="Комиссия площадки"
            :value="`−${formatPrice(order.commission_amount, order.currency)}`"
            negative
          />
          <BaseMoneyRow
            v-if="order.payout_amount !== null"
            label="Продавец получит"
            :value="formatPrice(order.payout_amount, order.currency)"
            variant="total"
          />
        </div>

        <p v-if="order.status === 'pending_payment'" class="text-xs text-ink-faint">
          Ждём подтверждения оплаты от ЮKassa…
        </p>
        <p
          v-if="order.status === 'paid_escrow' && order.escrow_hold_until"
          class="text-xs text-ink-faint"
        >
          Деньги удерживаются платформой до
          {{ new Date(order.escrow_hold_until).toLocaleDateString('ru-RU') }}
        </p>
      </div>

      <div class="flex flex-col rounded-card border border-hairline bg-surface p-4">
        <h2 class="mb-3 text-sm font-semibold text-ink-soft">Как идёт сделка</h2>
        <BaseStatusStep
          v-for="(step, index) in dealSteps"
          :key="step.title"
          :title="step.title"
          :meta="step.meta"
          :state="step.state"
          :last="index === dealSteps.length - 1"
        />
      </div>

      <div
        v-if="order.counterpart_address"
        class="flex items-center justify-between gap-2 rounded-2xl bg-surface-soft px-3 py-2"
      >
        <span class="inline-flex min-w-0 items-center gap-1.5 text-xs text-ink-soft">
          <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
          <span class="truncate">{{ order.counterpart_address }}</span>
        </span>
        <a
          v-if="order.counterpart_location"
          :href="yandexRouteUrl(order.counterpart_location.lat, order.counterpart_location.lng)"
          target="_blank"
          rel="noopener"
          class="shrink-0 text-xs font-bold text-accent-text"
        >
          Как добраться
        </a>
      </div>

      <BaseAlert v-if="error" tone="error">{{ error }}</BaseAlert>

      <BaseButton v-if="order.status === 'pending_payment'" variant="ghost" @click="cancel">
        Отменить заказ
      </BaseButton>

      <template v-if="order.status === 'paid_escrow'">
        <BaseButton v-if="!hasConfirmed" @click="confirm">Подтвердить получение</BaseButton>
        <BaseAlert v-else tone="success">Вы подтвердили сделку, ждём вторую сторону</BaseAlert>

        <BaseButton v-if="!showDisputeForm" variant="ghost" @click="showDisputeForm = true">
          Открыть спор
        </BaseButton>
        <div v-else class="flex flex-col gap-2">
          <BaseTextarea v-model="disputeReason" :rows="3" placeholder="Опишите проблему" />
          <div class="flex gap-2">
            <BaseButton @click="submitDispute">Отправить</BaseButton>
            <BaseButton variant="ghost" @click="showDisputeForm = false">Отмена</BaseButton>
          </div>
        </div>
      </template>

      <div
        v-if="order.status === 'completed' && isBuyer && !reviewSubmitted"
        class="flex flex-col gap-2 rounded-card border border-hairline bg-surface p-4"
      >
        <span class="text-sm font-semibold text-ink">Оставить отзыв о продавце</span>
        <div class="flex gap-1">
          <button
            v-for="star in [1, 2, 3, 4, 5]"
            :key="star"
            type="button"
            :aria-label="`Оценка ${star}`"
            @click="reviewRating = star"
          >
            <Star
              class="size-7 fill-current"
              :class="star <= reviewRating ? 'text-gold' : 'text-hairline'"
            />
          </button>
        </div>
        <BaseTextarea v-model="reviewComment" :rows="2" placeholder="Комментарий (необязательно)" />
        <BaseAlert v-if="reviewError" tone="error">{{ reviewError }}</BaseAlert>
        <BaseButton @click="submitReview">Отправить отзыв</BaseButton>
      </div>
      <BaseAlert
        v-else-if="order.status === 'completed' && isBuyer && reviewSubmitted"
        tone="success"
        >Спасибо за отзыв!</BaseAlert
      >
    </div>
  </div>
</template>
