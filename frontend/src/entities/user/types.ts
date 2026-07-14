export type AccountType = 'owner' | 'breeder' | 'shelter' | 'admin' | 'moderator'

export interface User {
  id: string
  phone: string
  email: string | null
  account_type: AccountType
  status: string
  created_at: string
}

export interface AuthResponse {
  user: User
  token: string
}
