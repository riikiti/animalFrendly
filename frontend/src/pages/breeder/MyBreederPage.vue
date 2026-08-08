<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as breederApi from '@/entities/breeder/api'
import type { Breeder } from '@/entities/breeder/types'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const statusLabels: Record<string, string> = {
  pending: 'На рассмотрении модератора',
  verified: 'Подтверждён',
  rejected: 'Отклонён',
}

const router = useRouter()

const isLoading = ref(true)
const breeder = ref<Breeder | null>(null)
const isRegistering = ref(false)
const registerError = ref('')

onMounted(async () => {
  const response = await breederApi.getMyBreeder()
  breeder.value = response.data
  isLoading.value = false
})

async function register(): Promise<void> {
  registerError.value = ''
  isRegistering.value = true

  try {
    const response = await breederApi.registerBreeder()
    breeder.value = response.data
  } catch (e) {
    registerError.value =
      e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isRegistering.value = false
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.back()">←</button>
      <span class="font-display text-xl font-bold text-ink">Заводчик</span>
    </div>

    <template v-if="!isLoading">
      <div v-if="!breeder" class="flex flex-col gap-3 px-2">
        <p class="text-xs text-ink-faint">
          Подтверждение заводчика — не обязательно, чтобы продавать питомцев, но добавляет бейдж
          доверия на витрине для покупателей. Подать заявку можно один раз, дальше её рассмотрит
          модератор.
        </p>
        <p v-if="registerError" class="text-xs text-danger">{{ registerError }}</p>
        <BaseButton :disabled="isRegistering" @click="register">
          {{ isRegistering ? 'Отправляем…' : 'Подать заявку на верификацию' }}
        </BaseButton>
      </div>

      <div v-else class="flex flex-col gap-3 px-2">
        <div
          class="flex items-center justify-between rounded-card border border-hairline bg-surface p-4"
        >
          <span class="text-sm text-ink-soft">Статус</span>
          <span
            class="rounded-full px-2 py-1 text-xs font-semibold"
            :class="{
              'bg-accent/20 text-accent-ink': breeder.verification_status === 'pending',
              'bg-teal-soft text-teal': breeder.verification_status === 'verified',
              'bg-clay-soft text-clay': breeder.verification_status === 'rejected',
            }"
          >
            {{ statusLabels[breeder.verification_status] }}
          </span>
        </div>
        <p
          v-if="breeder.verification_status !== 'verified'"
          class="rounded-card bg-surface-soft p-6 text-center text-sm text-ink-soft"
        >
          Как только модератор подтвердит заявку, на витрине рядом с вашими объявлениями появится
          бейдж «Подтверждённый заводчик».
        </p>
      </div>

      <BaseButton variant="ghost" @click="router.push({ name: 'my-listings' })">
        Мои объявления
      </BaseButton>
    </template>
  </div>
</template>
