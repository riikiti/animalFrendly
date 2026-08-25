import { apiRequest } from '@/shared/api/http'
import type {
  Cart,
  DeliveryMethod,
  DeliveryOption,
  ShopCategory,
  ShopOrder,
  ShopProduct,
} from './types'

export interface ProductFilters {
  category?: string
  q?: string
}

export interface SaveProductPayload {
  category_id: string
  title: string
  description?: string | null
  price_amount: number
  stock: number
  photo_url?: string | null
}

export interface CheckoutPayload {
  delivery_method: DeliveryMethod
  delivery_address?: string | null
}

function query(filters: ProductFilters): string {
  const params = new URLSearchParams()
  if (filters.category) params.set('category', filters.category)
  if (filters.q) params.set('q', filters.q)
  const search = params.toString()

  return search === '' ? '' : `?${search}`
}

export function listCategories(): Promise<{ data: ShopCategory[] }> {
  return apiRequest('/api/v1/shop/categories', { auth: false })
}

export function listProducts(filters: ProductFilters = {}): Promise<{ data: ShopProduct[] }> {
  return apiRequest(`/api/v1/shop/products${query(filters)}`, { auth: false })
}

export function getProduct(id: string): Promise<{ data: ShopProduct }> {
  return apiRequest(`/api/v1/shop/products/${id}`, { auth: false })
}

export function listMyProducts(): Promise<{ data: ShopProduct[] }> {
  return apiRequest('/api/v1/shop/my-products')
}

export function createProduct(payload: SaveProductPayload): Promise<{ data: ShopProduct }> {
  return apiRequest('/api/v1/shop/products', { method: 'POST', body: payload })
}

export function updateProduct(
  id: string,
  payload: SaveProductPayload,
): Promise<{ data: ShopProduct }> {
  return apiRequest(`/api/v1/shop/products/${id}`, { method: 'PATCH', body: payload })
}

export function uploadProductPhoto(id: string, file: File): Promise<{ data: ShopProduct }> {
  const formData = new FormData()
  formData.append('photo', file)

  return apiRequest(`/api/v1/shop/products/${id}/photo`, { method: 'POST', body: formData })
}

export function archiveProduct(id: string): Promise<{ data: ShopProduct }> {
  return apiRequest(`/api/v1/shop/products/${id}/archive`, { method: 'POST' })
}

export function getCart(): Promise<{ data: Cart }> {
  return apiRequest('/api/v1/shop/cart')
}

export function addToCart(productId: string, quantity = 1): Promise<{ data: Cart }> {
  return apiRequest('/api/v1/shop/cart', {
    method: 'POST',
    body: { product_id: productId, quantity },
  })
}

export function setCartQuantity(productId: string, quantity: number): Promise<{ data: Cart }> {
  return apiRequest(`/api/v1/shop/cart/${productId}`, { method: 'PATCH', body: { quantity } })
}

export function removeFromCart(productId: string): Promise<{ data: Cart }> {
  return apiRequest(`/api/v1/shop/cart/${productId}`, { method: 'DELETE' })
}

export function listDeliveryOptions(): Promise<{ data: DeliveryOption[] }> {
  return apiRequest('/api/v1/shop/delivery-options')
}

/** Оформление разъезжается на заказ по каждому продавцу, но платёж один. */
export function checkout(payload: CheckoutPayload): Promise<{
  data: ShopOrder[]
  checkout_id: string
  amount: number
  currency: string
  confirmation_url: string
}> {
  return apiRequest('/api/v1/shop/orders', { method: 'POST', body: payload })
}

export function listOrders(role: 'buyer' | 'seller' = 'buyer'): Promise<{ data: ShopOrder[] }> {
  return apiRequest(`/api/v1/shop/orders?role=${role}`)
}

export function getOrder(id: string): Promise<{ data: ShopOrder }> {
  return apiRequest(`/api/v1/shop/orders/${id}`)
}

export function shipOrder(id: string): Promise<{ data: ShopOrder }> {
  return apiRequest(`/api/v1/shop/orders/${id}/ship`, { method: 'POST' })
}

export function confirmOrder(id: string): Promise<{ data: ShopOrder }> {
  return apiRequest(`/api/v1/shop/orders/${id}/confirm`, { method: 'POST' })
}

export function disputeOrder(id: string): Promise<{ data: ShopOrder }> {
  return apiRequest(`/api/v1/shop/orders/${id}/dispute`, { method: 'POST' })
}

export function cancelOrder(id: string): Promise<{ data: ShopOrder }> {
  return apiRequest(`/api/v1/shop/orders/${id}/cancel`, { method: 'POST' })
}
