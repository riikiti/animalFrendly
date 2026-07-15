export type SubscriptionStatus = 'pending_payment' | 'active' | 'canceled' | 'expired' | 'past_due'
export type BillingPeriod = 'month' | 'year'

export interface PlanFeatures {
  daily_likes: number | null
  super_likes_per_week: number
  boosts_per_month: number
  marketplace_commission_bps: number
  who_liked_me?: boolean
  priority_in_feed?: boolean
  advanced_filters?: boolean
}

export interface SubscriptionPlan {
  id: number
  slug: string
  name_ru: string
  price_amount: number
  currency: string
  period: BillingPeriod
  features: PlanFeatures
}

export interface Subscription {
  id: string
  plan_id: number
  status: SubscriptionStatus
  started_at: string | null
  current_period_ends_at: string | null
  auto_renew: boolean
  canceled_at: string | null
}
