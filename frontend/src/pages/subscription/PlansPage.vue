<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSubscriptionStore } from '@/entities/subscription/model'
import type { SubscriptionPlan } from '@/entities/subscription/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const subscriptionStore = useSubscriptionStore()

const isLoading = ref(true)
const subscribingSlug = ref<string | null>(null)
const error = ref('')

onMounted(async () => {
  await Promise.all([subscriptionStore.fetchPlans(), subscriptionStore.fetchMySubscription()])
  isLoading.value = false
})

function formatPrice(minorUnits: number, currency: string): string {
  if (minorUnits === 0) return 'Бесплатно'
  const amount = (minorUnits / 100).toLocaleString('ru-RU')
  return `${amount} ${currency === 'RUB' ? '₽' : currency} / мес`
}

function featureLines(plan: SubscriptionPlan): string[] {
  const lines: string[] = []
  lines.push(
    plan.features.daily_likes === null
      ? 'Лайки без ограничений'
      : `${plan.features.daily_likes} лайков в сутки`,
  )
  lines.push(`${plan.features.super_likes_per_week} супер-лайков в неделю`)
  lines.push(
    plan.features.boosts_per_month > 0
      ? `${plan.features.boosts_per_month} буста анкеты в месяц`
      : 'Без буста анкеты',
  )
  lines.push(`Комиссия маркетплейса ${plan.features.marketplace_commission_bps / 100}%`)

  return lines
}

function isCurrentPlan(plan: SubscriptionPlan): boolean {
  return subscriptionStore.currentPlan?.slug === plan.slug
}

async function subscribeToPlan(slug: string): Promise<void> {
  error.value = ''
  subscribingSlug.value = slug

  try {
    const result = await subscriptionStore.subscribeToPlan(slug)
    window.location.href = result.confirmation_url
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
    subscribingSlug.value = null
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.back()">←</button>
      <span class="font-display text-lg text-ink">Тарифы</span>
    </div>

    <p v-if="error" class="px-2 text-xs text-danger">{{ error }}</p>

    <div v-if="!isLoading" class="flex flex-col gap-3 pb-6">
      <div
        v-for="plan in subscriptionStore.plans"
        :key="plan.slug"
        class="card flex flex-col gap-2 rounded-2xl border border-hairline p-4"
      >
        <div class="flex items-center justify-between">
          <span class="text-base font-semibold text-ink">{{ plan.name_ru }}</span>
          <span class="text-sm font-semibold text-accent-ink">
            {{ formatPrice(plan.price_amount, plan.currency) }}
          </span>
        </div>

        <ul class="flex flex-col gap-1">
          <li v-for="line in featureLines(plan)" :key="line" class="text-xs text-ink-soft">
            · {{ line }}
          </li>
        </ul>

        <p v-if="isCurrentPlan(plan)" class="text-xs font-semibold text-teal">Ваш текущий тариф</p>
        <BaseButton
          v-else-if="plan.price_amount > 0"
          :disabled="subscribingSlug === plan.slug"
          @click="subscribeToPlan(plan.slug)"
        >
          {{ subscribingSlug === plan.slug ? 'Переходим к оплате…' : 'Оформить' }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>
