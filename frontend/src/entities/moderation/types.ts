export type ReportTargetType = 'pet' | 'listing' | 'user' | 'message'
export type ReportReason = 'spam' | 'inappropriate' | 'scam' | 'other'
export type ReportStatus = 'pending' | 'reviewed' | 'dismissed'

export interface Report {
  id: string
  reporter_id: string
  target_type: ReportTargetType
  target_id: string
  reason: ReportReason
  comment: string | null
  status: ReportStatus
  reviewed_by: string | null
  reviewed_at: string | null
  created_at: string
}

export interface Review {
  id: string
  order_id: string | null
  adoption_request_id: string | null
  author_id: string
  target_user_id: string
  rating: number
  comment: string | null
  created_at: string
}

export interface RatingSummary {
  average: number
  count: number
}
