export type BreederVerificationStatus = 'pending' | 'verified' | 'rejected'

export interface Breeder {
  id: string
  owner_user_id: string
  verification_status: BreederVerificationStatus
  verified_at: string | null
}
