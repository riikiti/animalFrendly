import { apiRequest } from '@/shared/api/http'
import type { Dispute, Listing, Order, OrderRole } from './types'

export interface CreateListingPayload {
  species_id: number
  breed_id: number | null
  name: string
  sex: 'male' | 'female'
  birthdate?: string | null
  description?: string | null
  is_vaccinated?: boolean
  price_amount: number
  currency?: string
  parent_pet_id?: string | null
}

export function listListings(): Promise<{ data: Listing[] }> {
  return apiRequest('/api/v1/listings')
}

export function listMyListings(): Promise<{ data: Listing[] }> {
  return apiRequest('/api/v1/listings/me')
}

export function createListing(payload: CreateListingPayload): Promise<{ data: Listing }> {
  return apiRequest('/api/v1/listings', { method: 'POST', body: payload })
}

export function publishListing(listingId: string): Promise<{ data: Listing }> {
  return apiRequest(`/api/v1/listings/${listingId}/publish`, { method: 'POST' })
}

export function archiveListing(listingId: string): Promise<{ data: Listing }> {
  return apiRequest(`/api/v1/listings/${listingId}/archive`, { method: 'POST' })
}

export function purchaseListing(
  listingId: string,
): Promise<{ data: { order: Order; confirmation_url: string } }> {
  return apiRequest(`/api/v1/listings/${listingId}/orders`, { method: 'POST' })
}

export function listMyOrders(role: OrderRole): Promise<{ data: Order[] }> {
  return apiRequest(`/api/v1/orders/me?role=${role}`)
}

export function getOrder(orderId: string): Promise<{ data: Order }> {
  return apiRequest(`/api/v1/orders/${orderId}`)
}

export function confirmOrder(orderId: string): Promise<{ data: Order }> {
  return apiRequest(`/api/v1/orders/${orderId}/confirm`, { method: 'POST' })
}

export function cancelOrder(orderId: string): Promise<{ data: Order }> {
  return apiRequest(`/api/v1/orders/${orderId}/cancel`, { method: 'POST' })
}

export function openDispute(orderId: string, reason: string): Promise<{ data: Dispute }> {
  return apiRequest(`/api/v1/orders/${orderId}/disputes`, { method: 'POST', body: { reason } })
}
