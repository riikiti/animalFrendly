import { apiRequest } from '@/shared/api/http'
import type { RatingSummary, Report, ReportReason, ReportTargetType, Review } from './types'

export interface SubmitReportPayload {
  target_type: ReportTargetType
  target_id: string
  reason: ReportReason
  comment?: string | null
}

export function submitReport(payload: SubmitReportPayload): Promise<{ data: Report }> {
  return apiRequest('/api/v1/reports', { method: 'POST', body: payload })
}

export function listReports(): Promise<{ data: Report[] }> {
  return apiRequest('/api/v1/moderation/reports')
}

export function reviewReport(reportId: string): Promise<{ data: Report }> {
  return apiRequest(`/api/v1/moderation/reports/${reportId}/review`, { method: 'POST' })
}

export function dismissReport(reportId: string): Promise<{ data: Report }> {
  return apiRequest(`/api/v1/moderation/reports/${reportId}/dismiss`, { method: 'POST' })
}

export function banUser(userId: string): Promise<{ data: unknown }> {
  return apiRequest(`/api/v1/moderation/users/${userId}/ban`, { method: 'POST' })
}

export function unbanUser(userId: string): Promise<{ data: unknown }> {
  return apiRequest(`/api/v1/moderation/users/${userId}/unban`, { method: 'POST' })
}

export interface SubmitReviewPayload {
  order_id?: string
  adoption_request_id?: string
  rating: number
  comment?: string | null
}

export function submitReview(payload: SubmitReviewPayload): Promise<{ data: Review }> {
  return apiRequest('/api/v1/reviews', { method: 'POST', body: payload })
}

export function getUserRating(userId: string): Promise<{ data: RatingSummary }> {
  return apiRequest(`/api/v1/users/${userId}/rating`)
}
