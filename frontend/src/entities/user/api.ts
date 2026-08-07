import { apiRequest } from '@/shared/api/http'
import type { AuthResponse, User } from './types'

export interface RegisterPayload {
  phone: string
  name?: string
  password: string
  password_confirmation: string
  personal_data_consent: boolean
}

export interface LoginPayload {
  phone: string
  password: string
  /** Продлевает жизнь токена с суток до 90 дней. */
  remember?: boolean
}

/** login — вход по коду, password_reset — восстановление пароля. */
export type PhoneCodePurpose = 'login' | 'password_reset'

export interface PhoneCodeLoginPayload {
  phone: string
  code: string
  remember?: boolean
}

export interface ResetPasswordPayload {
  phone: string
  code: string
  password: string
  password_confirmation: string
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

export function requestPhoneCode(
  phone: string,
  purpose: PhoneCodePurpose,
): Promise<{ message: string }> {
  return apiRequest('/api/v1/auth/phone-code', {
    method: 'POST',
    body: { phone, purpose },
    auth: false,
  })
}

export function loginWithPhoneCode(payload: PhoneCodeLoginPayload): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/v1/auth/phone-code/login', {
    method: 'POST',
    body: payload,
    auth: false,
  })
}

export function resetPassword(payload: ResetPasswordPayload): Promise<AuthResponse> {
  return apiRequest<AuthResponse>('/api/v1/auth/password/reset', {
    method: 'POST',
    body: payload,
    auth: false,
  })
}

/** Список провайдеров, у которых заданы ключи. Пустой — кнопок соцвхода нет. */
export function socialProviders(): Promise<{ data: string[] }> {
  return apiRequest('/api/v1/auth/social/providers', { auth: false })
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
