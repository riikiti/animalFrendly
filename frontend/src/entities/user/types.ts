export type AccountType = 'owner' | 'breeder' | 'shelter' | 'admin' | 'moderator'

export interface User {
  id: string
  name: string | null
  avatar_url: string | null
  phone: string
  email: string | null
  account_type: AccountType
  status: string
  city: string | null
  created_at: string
}

export interface AuthResponse {
  user: User
  token: string
}
