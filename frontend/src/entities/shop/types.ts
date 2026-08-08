export type ProductStatus = 'draft' | 'published' | 'archived'

export type ShopOrderStatus =
  | 'pending_payment'
  | 'paid_escrow'
  | 'shipped'
  | 'completed'
  | 'disputed'
  | 'refunded'
  | 'cancelled'

export type DeliveryMethod = 'pickup' | 'pvz' | 'courier'

export interface ShopCategory {
  id: string
  slug: string
  name: string
}

export interface ShopProduct {
  id: string
  seller_id: string
  category_id: string
  title: string
  description: string | null
  /** Копейки. Форматировать через shared/lib/money. */
  price_amount: number
  currency: string
  stock: number
  status: ProductStatus
  photo_url: string | null
}

export interface CartLine {
  product: ShopProduct
  quantity: number
}

export interface Cart {
  items: CartLine[]
  /** Корзина держит товары одного продавца; null, когда она пуста. */
  seller_id: string | null
  total_amount: number
  currency: string
}

export interface DeliveryOption {
  value: DeliveryMethod
  label: string
  price_amount: number
  needs_address: boolean
}

export interface ShopOrderItem {
  product_id: string
  title: string
  price_amount: number
  quantity: number
}

export interface ShopOrder {
  id: string
  buyer_id: string
  seller_id: string
  status: ShopOrderStatus
  items: ShopOrderItem[]
  items_amount: number
  delivery_amount: number
  amount: number
  commission_amount: number | null
  payout_amount: number | null
  currency: string
  delivery_method: DeliveryMethod
  delivery_label: string
  delivery_address: string | null
  escrow_hold_until: string | null
  buyer_confirmed_at: string | null
  seller_confirmed_at: string | null
}
