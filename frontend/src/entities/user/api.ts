import { apiRequest } from '@/shared/api/http'
import type { AuthResponse, User } from './types'

export interface RegisterPayload {
  phone: string
  password: string
  password_confirmation: string
  personal_data_consent: boolean
}

export interface LoginPayload {
  phone: string
  password: string
}

export function register(payload: RegisterPayload): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/v1/auth/register', {
    method: 'POST',
    body: payload,
    auth: false,
  })
}

export function login(payload: LoginPayload): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/v1/auth/login', {
    method: 'POST',
    body: payload,
    auth: false,
  })
}

export function me(): Promise<User> {
  return apiRequest<User>('/api/v1/auth/me')
}

export function logout(): Promise<{ message: string }> {
  return apiRequest('/api/v1/auth/logout', { method: 'POST' })
}

export function updateProfile(payload: {
  name?: string | null
  address?: string | null
}): Promise<User> {
  return apiRequest<User>('/api/v1/auth/me', { method: 'PATCH', body: payload })
}

export function uploadAvatar(file: File): Promise<User> {
  const formData = new FormData()
  formData.append('photo', file)

  return apiRequest<User>('/api/v1/auth/me/avatar', { method: 'POST', body: formData })
}
