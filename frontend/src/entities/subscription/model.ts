import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as subscriptionApi from './api'
import type { Subscription, SubscriptionPlan } from './types'

export const useSubscriptionStore = defineStore('subscription', () => {
  const plans = ref<SubscriptionPlan[]>([])
  const currentSubscription = ref<Subscription | null>(null)
  const currentPlan = ref<SubscriptionPlan | null>(null)
  const isLoading = ref(false)

  async function fetchPlans(): Promise<void> {
    isLoading.value = true
    try {
      const response = await subscriptionApi.listPlans()
      plans.value = response.data
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMySubscription(): Promise<void> {
    isLoading.value = true
    try {
      const response = await subscriptionApi.getMySubscription()
      currentSubscription.value = response.data.subscription
      currentPlan.value = response.data.plan
    } finally {
      isLoading.value = false
    }
  }

  async function subscribeToPlan(
    planSlug: string,
  ): Promise<{ subscription: Subscription; confirmation_url: string }> {
    const response = await subscriptionApi.subscribe(planSlug)
    currentSubscription.value = response.data.subscription

    return response.data
  }

  async function cancelAutoRenew(): Promise<Subscription> {
    const response = await subscriptionApi.cancelSubscription()
    currentSubscription.value = response.data

    return response.data
  }

  return {
    plans,
    currentSubscription,
    currentPlan,
    isLoading,
    fetchPlans,
    fetchMySubscription,
    subscribeToPlan,
    cancelAutoRenew,
  }
})
