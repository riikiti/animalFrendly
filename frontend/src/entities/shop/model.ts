import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as shopApi from './api'
import type { Cart } from './types'

/**
 * Корзина живёт в сторе, потому что её счётчик виден в шапке магазина с любого экрана,
 * а не только на самой странице корзины.
 */
export const useCartStore = defineStore('shop-cart', () => {
  const cart = ref<Cart>({ items: [], groups: [], total_amount: 0, currency: 'RUB' })
  const isLoading = ref(false)

  const count = computed(() => cart.value.items.reduce((sum, line) => sum + line.quantity, 0))
  const isEmpty = computed(() => cart.value.items.length === 0)

  async function fetch(): Promise<void> {
    isLoading.value = true
    try {
      cart.value = (await shopApi.getCart()).data
    } finally {
      isLoading.value = false
    }
  }

  async function add(productId: string, quantity = 1): Promise<void> {
    cart.value = (await shopApi.addToCart(productId, quantity)).data
  }

  async function setQuantity(productId: string, quantity: number): Promise<void> {
    cart.value = (await shopApi.setCartQuantity(productId, quantity)).data
  }

  async function remove(productId: string): Promise<void> {
    cart.value = (await shopApi.removeFromCart(productId)).data
  }

  function clearLocally(): void {
    cart.value = { items: [], groups: [], total_amount: 0, currency: 'RUB' }
  }

  return { cart, isLoading, count, isEmpty, fetch, add, setQuantity, remove, clearLocally }
})
