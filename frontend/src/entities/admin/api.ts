import { apiRequest } from '@/shared/api/http'
import type { AdminSummary, AuditLogEntry } from './types'

export function getSummary(): Promise<{ data: AdminSummary }> {
  return apiRequest('/api/v1/admin/summary')
}

export function getAuditLog(): Promise<{ data: AuditLogEntry[] }> {
  return apiRequest('/api/v1/admin/audit-log')
}
