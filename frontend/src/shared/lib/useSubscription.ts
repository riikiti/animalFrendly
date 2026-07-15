import { computed, type ComputedRef } from 'vue'
import { useSubscriptionStore } from '@/entities/subscription/model'

interface UseSubscription {
  hasActiveSubscription: ComputedRef<boolean>
  isPlus: ComputedRef<boolean>
  isPremium: ComputedRef<boolean>
}

/**
 * Тонкий composable поверх useSubscriptionStore — используется для проактивного UX-гейтинга
 * (например, скрыть/задизейблить кнопку буста, если лимит явно исчерпан). Не единственная
 * защита: реальный лимит всегда проверяется на бэкенде (см. SubscriptionFeatureGate).
 */
export function useSubscription(): UseSubscription {
  const store = useSubscriptionStore()

  const hasActiveSubscription = computed(() => store.currentSubscription?.status === 'active')
  const isPlus = computed(() => hasActiveSubscription.value && store.currentPlan?.slug === 'plus')
  const isPremium = computed(
    () => hasActiveSubscription.value && store.currentPlan?.slug === 'premium',
  )

  return { hasActiveSubscription, isPlus, isPremium }
}
