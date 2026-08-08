<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as breederApi from '@/entities/breeder/api'
import type { Breeder } from '@/entities/breeder/types'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const router = useRouter()

const breeders = ref<Breeder[]>([])
const isLoading = ref(true)
const error = ref('')

async function load(): Promise<void> {
  isLoading.value = true
  const response = await breederApi.listPendingBreederVerifications()
  breeders.value = response.data
  isLoading.value = false
}

onMounted(load)

async function decide(breederId: string, approve: boolean): Promise<void> {
  error.value = ''
  try {
    await breederApi.verifyBreeder(breederId, approve)
    breeders.value = breeders.value.filter((b) => b.id !== breederId)
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-4xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.push({ name: 'admin-dashboard' })">
        ←
      </button>
      <span class="font-display text-xl font-bold text-ink">Заводчики на верификации</span>
    </div>

    <p v-if="error" class="px-2 text-xs text-danger">{{ error }}</p>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2">
      <p
        v-if="breeders.length === 0"
        class="rounded-card bg-surface-soft p-6 text-center text-sm text-ink-soft"
      >
        Нет заводчиков, ожидающих верификации
      </p>

      <div
        v-for="breeder in breeders"
        :key="breeder.id"
        class="flex flex-col gap-2 rounded-card border border-hairline bg-surface p-4"
      >
        <span class="text-sm font-semibold text-ink">Пользователь {{ breeder.owner_user_id }}</span>

        <div class="flex gap-2">
          <BaseButton @click="decide(breeder.id, true)">Подтвердить</BaseButton>
          <BaseButton variant="outline" @click="decide(breeder.id, false)">Отклонить</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
