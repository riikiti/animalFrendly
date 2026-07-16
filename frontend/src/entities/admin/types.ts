export interface AdminSummary {
  pending_reports: number
  pending_shelter_verifications: number
  pending_breeder_verifications: number
  open_disputes: number
}

export interface AuditLogEntry {
  id: string
  actor_id: string | null
  action: string
  entity_type: string
  entity_id: string
  payload: Record<string, unknown>
  created_at: string
}
