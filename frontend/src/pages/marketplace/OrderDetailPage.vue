<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as marketplaceApi from '@/entities/marketplace/api'
import type { Order } from '@/entities/marketplace/types'
import * as moderationApi from '@/entities/moderation/api'
import { useUserStore } from '@/entities/user/model'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { ApiError } from '@/shared/api/http'
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

function formatPrice(minorUnits: number, currency: string): string {
  const amount = (minorUnits / 100).toLocaleString('ru-RU')
  return `${amount} ${currency === 'RUB' ? '₽' : currency}`
}

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
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6">
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.push({ name: 'my-orders' })">←</button>
      <span class="font-display text-lg text-ink">Заказ</span>
    </div>

    <div v-if="!isLoading && order" class="flex flex-col gap-4 px-2">
      <div class="flex flex-col gap-2 rounded-2xl border border-hairline p-4">
        <div class="flex items-center justify-between">
          <span class="text-lg font-semibold text-ink">{{
            formatPrice(order.amount, order.currency)
          }}</span>
          <span class="rounded-full bg-surface-soft px-2 py-1 text-xs font-semibold text-ink-soft">
            {{ statusLabels[order.status] }}
          </span>
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

      <div
        v-if="order.counterpart_address"
        class="flex items-center justify-between gap-2 rounded-2xl bg-surface-soft px-3 py-2"
      >
        <span class="text-xs text-ink-soft">📍 {{ order.counterpart_address }}</span>
        <a
          v-if="order.counterpart_location"
          :href="yandexRouteUrl(order.counterpart_location.lat, order.counterpart_location.lng)"
          target="_blank"
          rel="noopener"
          class="shrink-0 text-xs font-semibold text-teal"
        >
          Как добраться
        </a>
      </div>

      <p v-if="error" class="text-xs text-danger">{{ error }}</p>

      <BaseButton v-if="order.status === 'pending_payment'" variant="ghost" @click="cancel">
        Отменить заказ
      </BaseButton>

      <template v-if="order.status === 'paid_escrow'">
        <BaseButton v-if="!hasConfirmed" @click="confirm">Подтвердить получение</BaseButton>
        <p v-else class="text-xs text-teal">Вы подтвердили сделку, ждём вторую сторону</p>

        <BaseButton v-if="!showDisputeForm" variant="ghost" @click="showDisputeForm = true">
          Открыть спор
        </BaseButton>
        <div v-else class="flex flex-col gap-2">
          <textarea
            v-model="disputeReason"
            rows="3"
            placeholder="Опишите проблему"
            class="rounded-xl bg-surface-soft px-3 py-2 text-sm text-ink outline-none"
          ></textarea>
          <div class="flex gap-2">
            <BaseButton @click="submitDispute">Отправить</BaseButton>
            <BaseButton variant="ghost" @click="showDisputeForm = false">Отмена</BaseButton>
          </div>
        </div>
      </template>

      <div
        v-if="order.status === 'completed' && isBuyer && !reviewSubmitted"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-4"
      >
        <span class="text-sm font-semibold text-ink">Оставить отзыв о продавце</span>
        <div class="flex gap-1">
          <button
            v-for="star in [1, 2, 3, 4, 5]"
            :key="star"
            type="button"
            class="text-2xl"
            :class="star <= reviewRating ? 'text-accent' : 'text-hairline'"
            @click="reviewRating = star"
          >
            ★
          </button>
        </div>
        <textarea
          v-model="reviewComment"
          rows="2"
          placeholder="Комментарий (необязательно)"
          class="rounded-xl bg-surface-soft px-3 py-2 text-sm text-ink outline-none"
        ></textarea>
        <p v-if="reviewError" class="text-xs text-danger">{{ reviewError }}</p>
        <BaseButton @click="submitReview">Отправить отзыв</BaseButton>
      </div>
      <p
        v-else-if="order.status === 'completed' && isBuyer && reviewSubmitted"
        class="text-xs text-teal"
      >
        Спасибо за отзыв!
      </p>
    </div>
  </div>
</template>
