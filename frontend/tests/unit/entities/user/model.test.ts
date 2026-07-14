import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useUserStore } from '@/entities/user/model'
import * as userApi from '@/entities/user/api'
import { clearToken, getToken } from '@/shared/lib/tokenStorage'

describe('useUserStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    clearToken()
    vi.restoreAllMocks()
  })

  it('stores the token and current user after a successful login', async () => {
    vi.spyOn(userApi, 'login').mockResolvedValue({
      token: 'test-token',
      user: {
        id: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        phone: '+79261234567',
        email: null,
        account_type: 'owner',
        status: 'active',
        created_at: '2026-07-14T00:00:00+00:00',
      },
    })

    const store = useUserStore()
    await store.login({ phone: '+79261234567', password: 'correct-password' })

    expect(store.currentUser?.phone).toBe('+79261234567')
    expect(getToken()).toBe('test-token')
  })

  it('clears the token and current user on logout even if the request fails', async () => {
    vi.spyOn(userApi, 'login').mockResolvedValue({
      token: 'test-token',
      user: {
        id: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        phone: '+79261234567',
        email: null,
        account_type: 'owner',
        status: 'active',
        created_at: '2026-07-14T00:00:00+00:00',
      },
    })
    vi.spyOn(userApi, 'logout').mockRejectedValue(new Error('network error'))

    const store = useUserStore()
    await store.login({ phone: '+79261234567', password: 'correct-password' })

    await expect(store.logout()).rejects.toThrow('network error')

    expect(store.currentUser).toBeNull()
    expect(getToken()).toBeNull()
  })
})
