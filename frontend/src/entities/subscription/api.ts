import { apiRequest } from '@/shared/api/http'
import type { Subscription, SubscriptionPlan } from './types'

export function listPlans(): Promise<{ data: SubscriptionPlan[] }> {
  return apiRequest('/api/v1/subscriptions/plans')
}

export function getMySubscription(): Promise<{
  data: { subscription: Subscription | null; plan: SubscriptionPlan | null }
}> {
  return apiRequest('/api/v1/subscriptions/me')
}

export function subscribe(
  planSlug: string,
): Promise<{ data: { subscription: Subscription; confirmation_url: string } }> {
  return apiRequest('/api/v1/subscriptions', { method: 'POST', body: { plan_slug: planSlug } })
}

export function cancelSubscription(): Promise<{ data: Subscription }> {
  return apiRequest('/api/v1/subscriptions/cancel', { method: 'POST' })
}
