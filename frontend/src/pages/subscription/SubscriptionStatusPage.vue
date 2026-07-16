<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSubscriptionStore } from '@/entities/subscription/model'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const subscriptionStore = useSubscriptionStore()

const isLoading = ref(true)
const error = ref('')

const statusLabels: Record<string, string> = {
  pending_payment: 'Ожидает оплаты',
  active: 'Активна',
  canceled: 'Отменена',
  expired: 'Истекла',
  past_due: 'Проблема с оплатой',
}

let pollTimer: ReturnType<typeof setInterval> | null = null
let isMounted = true

async function load(): Promise<void> {
  await subscriptionStore.fetchMySubscription()

  if (subscriptionStore.currentSubscription?.status !== 'pending_payment' && pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(async () => {
  try {
    await load()
    if (subscriptionStore.currentSubscription?.status === 'pending_payment') {
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

async function cancelAutoRenew(): Promise<void> {
  error.value = ''
  try {
    await subscriptionStore.cancelAutoRenew()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.push({ name: 'home' })">←</button>
      <span class="font-display text-lg text-ink">Подписка</span>
    </div>

    <div v-if="!isLoading" class="flex flex-col gap-4 px-2">
      <div
        v-if="subscriptionStore.currentSubscription"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-4"
      >
        <div class="flex items-center justify-between">
          <span class="text-base font-semibold text-ink">
            {{ subscriptionStore.currentPlan?.name_ru ?? 'Тариф' }}
          </span>
          <span class="rounded-full bg-surface-soft px-2 py-1 text-xs font-semibold text-ink-soft">
            {{ statusLabels[subscriptionStore.currentSubscription.status] }}
          </span>
        </div>

        <p
          v-if="subscriptionStore.currentSubscription.status === 'pending_payment'"
          class="text-xs text-ink-faint"
        >
          Ждём подтверждения оплаты от ЮKassa…
        </p>
        <p
          v-if="
            subscriptionStore.currentSubscription.status === 'active' &&
            subscriptionStore.currentSubscription.current_period_ends_at
          "
          class="text-xs text-ink-faint"
        >
          Автопродление
          {{
            new Date(
              subscriptionStore.currentSubscription.current_period_ends_at,
            ).toLocaleDateString('ru-RU')
          }}
        </p>

        <p v-if="error" class="text-xs text-danger">{{ error }}</p>

        <BaseButton
          v-if="
            subscriptionStore.currentSubscription.status === 'active' &&
            subscriptionStore.currentSubscription.auto_renew
          "
          variant="ghost"
          @click="cancelAutoRenew"
        >
          Отменить автопродление
        </BaseButton>
        <p
          v-else-if="subscriptionStore.currentSubscription.status === 'active'"
          class="text-xs text-teal"
        >
          Автопродление отключено
        </p>
      </div>

      <p v-else class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint">
        У вас бесплатный тариф
      </p>

      <BaseButton variant="outline" @click="router.push({ name: 'subscription-plans' })">
        Смотреть тарифы
      </BaseButton>
    </div>
  </div>
</template>
