<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as shelterApi from '@/entities/shelter/api'
import type { Shelter } from '@/entities/shelter/types'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const router = useRouter()

const shelters = ref<Shelter[]>([])
const isLoading = ref(true)
const error = ref('')

async function load(): Promise<void> {
  isLoading.value = true
  const response = await shelterApi.listPendingShelterVerifications()
  shelters.value = response.data
  isLoading.value = false
}

onMounted(load)

async function decide(shelterId: string, approve: boolean): Promise<void> {
  error.value = ''
  try {
    await shelterApi.verifyShelter(shelterId, approve)
    shelters.value = shelters.value.filter((s) => s.id !== shelterId)
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.push({ name: 'admin-dashboard' })">
        ←
      </button>
      <span class="font-display text-lg text-ink">Приюты на верификации</span>
    </div>

    <p v-if="error" class="px-2 text-xs text-danger">{{ error }}</p>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2">
      <p
        v-if="shelters.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Нет приютов, ожидающих верификации
      </p>

      <div
        v-for="shelter in shelters"
        :key="shelter.id"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-4"
      >
        <span class="text-sm font-semibold text-ink">{{ shelter.legal_name }}</span>
        <p v-if="shelter.inn" class="text-xs text-ink-faint">ИНН: {{ shelter.inn }}</p>
        <p v-if="shelter.description" class="text-sm text-ink-soft">{{ shelter.description }}</p>

        <div class="flex gap-2">
          <BaseButton @click="decide(shelter.id, true)">Подтвердить</BaseButton>
          <BaseButton variant="outline" @click="decide(shelter.id, false)">Отклонить</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
