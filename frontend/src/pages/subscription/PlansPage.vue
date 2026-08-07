<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSubscriptionStore } from '@/entities/subscription/model'
import type { SubscriptionPlan } from '@/entities/subscription/types'
import { ChevronLeft } from 'lucide-vue-next'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseCheckRow from '@/shared/ui/components/BaseCheckRow.vue'
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
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.back()"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Тарифы</h1>
    </div>

    <BaseAlert v-if="error" tone="error" class="mx-2">{{ error }}</BaseAlert>

    <div v-if="!isLoading" class="flex flex-col gap-3 pb-6">
      <div
        v-for="plan in subscriptionStore.plans"
        :key="plan.slug"
        class="card flex flex-col gap-3.5 rounded-card border p-5 transition-colors"
        :class="
          isCurrentPlan(plan) ? 'border-accent bg-accent-soft' : 'border-hairline bg-surface'
        "
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex flex-col gap-1">
            <span class="font-display text-lg font-bold text-ink">{{ plan.name_ru }}</span>
            <BaseBadge v-if="isCurrentPlan(plan)" tone="accent">Ваш текущий тариф</BaseBadge>
          </div>
          <span class="shrink-0 text-right">
            <span class="font-display text-xl font-bold text-ink">{{
              formatPrice(plan.price_amount, plan.currency)
            }}</span>
            <span v-if="plan.price_amount > 0" class="block text-xs text-ink-faint">в месяц</span>
          </span>
        </div>

        <ul class="flex flex-col gap-2.5">
          <li v-for="line in featureLines(plan)" :key="line">
            <BaseCheckRow>{{ line }}</BaseCheckRow>
          </li>
        </ul>

        <BaseButton
          v-if="!isCurrentPlan(plan) && plan.price_amount > 0"
          size="lg"
          block
          :loading="subscribingSlug === plan.slug"
          @click="subscribeToPlan(plan.slug)"
        >
          {{ subscribingSlug === plan.slug ? 'Переходим к оплате…' : 'Оформить' }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>
