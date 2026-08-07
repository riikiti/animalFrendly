import { defineStore } from 'pinia'
import { ref } from 'vue'
import { clearToken, setToken } from '@/shared/lib/tokenStorage'
import * as userApi from './api'
import type { User } from './types'

export const useUserStore = defineStore('user', () => {
  const currentUser = ref<User | null>(null)
  const isLoading = ref(false)

  async function register(payload: userApi.RegisterPayload): Promise<void> {
    const response = await userApi.register(payload)
    setToken(response.token)
    currentUser.value = response.user
  }

  async function login(payload: userApi.LoginPayload): Promise<void> {
    const response = await userApi.login(payload)
    setToken(response.token)
    currentUser.value = response.user
  }

  async function loginWithPhoneCode(payload: userApi.PhoneCodeLoginPayload): Promise<void> {
    const response = await userApi.loginWithPhoneCode(payload)
    setToken(response.token)
    currentUser.value = response.user
  }

  async function resetPassword(payload: userApi.ResetPasswordPayload): Promise<void> {
    const response = await userApi.resetPassword(payload)
    setToken(response.token)
    currentUser.value = response.user
  }

  async function fetchCurrentUser(): Promise<void> {
    isLoading.value = true
    try {
      currentUser.value = await userApi.me()
    } finally {
      isLoading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await userApi.logout()
    } finally {
      clearToken()
      currentUser.value = null
    }
  }

  async function updateProfile(payload: {
    name?: string | null
    address?: string | null
  }): Promise<void> {
    currentUser.value = await userApi.updateProfile(payload)
  }

  async function uploadAvatar(file: File): Promise<void> {
    currentUser.value = await userApi.uploadAvatar(file)
  }

  return {
    currentUser,
    isLoading,
    register,
    loginWithPhoneCode,
    resetPassword,
    login,
    logout,
    fetchCurrentUser,
    updateProfile,
    uploadAvatar,
  }
})
