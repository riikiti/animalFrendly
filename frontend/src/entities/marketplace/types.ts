export type ListingStatus = 'draft' | 'published' | 'reserved' | 'sold' | 'archived'
export type OrderStatus =
  'pending_payment' | 'paid_escrow' | 'completed' | 'disputed' | 'refunded' | 'cancelled'
export type DisputeResolution = 'seller_wins' | 'buyer_wins'
export type OrderRole = 'buyer' | 'seller'

export interface ListingPet {
  name: string
  species_id: number
  breed_id: number | null
  sex: 'male' | 'female'
  description: string | null
  is_vaccinated: boolean
  parent_pet_id: string | null
  parent_name: string | null
}

export interface Listing {
  id: string
  seller_id: string
  seller_name: string | null
  seller_avatar_url: string | null
  seller_verified: boolean
  pet_id: string
  price_amount: number
  currency: string
  status: ListingStatus
  pet: ListingPet | null
}

export interface Order {
  id: string
  listing_id: string
  buyer_id: string
  seller_id: string
  amount: number
  currency: string
  commission_amount: number | null
  payout_amount: number | null
  status: OrderStatus
  buyer_confirmed_at: string | null
  seller_confirmed_at: string | null
  escrow_hold_until: string | null
  counterpart_address: string | null
  counterpart_location: { lat: number; lng: number } | null
}

export interface Dispute {
  id: string
  order_id: string
  opened_by: string
  reason: string
  resolution: DisputeResolution | null
  resolved_by: string | null
  resolved_at: string | null
}
