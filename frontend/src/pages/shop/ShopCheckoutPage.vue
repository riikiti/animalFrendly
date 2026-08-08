<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronLeft, Package, Store, Truck } from 'lucide-vue-next'
import * as shopApi from '@/entities/shop/api'
import { useCartStore } from '@/entities/shop/model'
import type { DeliveryMethod, DeliveryOption } from '@/entities/shop/types'
import { ApiError } from '@/shared/api/http'
import { formatPrice } from '@/shared/lib/money'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BaseMoneyRow from '@/shared/ui/components/BaseMoneyRow.vue'
import BasePaymentMethod from '@/shared/ui/components/BasePaymentMethod.vue'

const router = useRouter()
const cartStore = useCartStore()

const options = ref<DeliveryOption[]>([])
const method = ref<DeliveryMethod>('pvz')
const address = ref('')
const isSubmitting = ref(false)
const error = ref('')

const selected = computed(() => options.value.find((option) => option.value === method.value))
const deliveryPrice = computed(() => selected.value?.price_amount ?? 0)
const total = computed(() => cartStore.cart.total_amount + deliveryPrice.value)

// Комиссию площадки платит продавец, покупатель видит только товары и доставку —
// см. Shop\Domain\Entities\ShopOrder::markPaid.
onMounted(async () => {
  await Promise.all([cartStore.fetch(), loadOptions()])

  if (cartStore.isEmpty) await router.replace({ name: 'shop-cart' })
})

async function loadOptions(): Promise<void> {
  options.value = (await shopApi.listDeliveryOptions()).data
}

async function submit(): Promise<void> {
  error.value = ''
  isSubmitting.value = true

  try {
    const response = await shopApi.checkout({
      delivery_method: method.value,
      delivery_address: address.value.trim() || null,
    })

    cartStore.clearLocally()
    // ЮKassa возвращает покупателя на страницу заказа — там же видно статус эскроу.
    window.location.href = response.confirmation_url
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 pb-8 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-2 px-2">
      <button
        class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
        aria-label="Назад"
        @click="router.back()"
      >
        <ChevronLeft class="size-5" />
      </button>
      <h1 class="font-display text-xl font-bold text-ink">Оформление</h1>
    </div>

    <div class="flex flex-col gap-3 px-2">
      <h2 class="text-sm font-semibold text-ink-soft">Доставка</h2>
      <BasePaymentMethod
        v-for="option in options"
        :key="option.value"
        :title="option.label"
        :description="
          option.price_amount === 0 ? 'Бесплатно' : formatPrice(option.price_amount, 'RUB')
        "
        :selected="method === option.value"
        @select="method = option.value"
      >
        <template #icon>
          <Truck v-if="option.value === 'courier'" class="size-[17px]" />
          <Package v-else-if="option.value === 'pvz'" class="size-[17px]" />
          <Store v-else class="size-[17px]" />
        </template>
      </BasePaymentMethod>

      <BaseInput
        v-if="selected?.needs_address"
        v-model="address"
        label="Адрес"
        placeholder="Город, улица, дом"
      />
    </div>

    <div class="flex flex-col gap-2 rounded-card border border-hairline bg-surface p-4 mx-2">
      <BaseMoneyRow
        label="Товары"
        :value="formatPrice(cartStore.cart.total_amount, cartStore.cart.currency)"
      />
      <BaseMoneyRow
        label="Доставка"
        :value="deliveryPrice === 0 ? 'бесплатно' : formatPrice(deliveryPrice, 'RUB')"
      />
      <div class="border-t border-hairline pt-2.5">
        <BaseMoneyRow
          label="Итого"
          :value="formatPrice(total, cartStore.cart.currency)"
          variant="total"
        />
      </div>
    </div>

    <BaseAlert v-if="error" tone="error" class="mx-2">{{ error }}</BaseAlert>

    <div class="mt-auto px-2">
      <BaseButton size="lg" block :loading="isSubmitting" @click="submit"
        >Перейти к оплате</BaseButton
      >
      <p class="mt-2 text-center text-xs text-ink-faint">
        Деньги придержим до подтверждения получения
      </p>
    </div>
  </div>
</template>
