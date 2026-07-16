<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as adminApi from '@/entities/admin/api'
import type { AdminSummary } from '@/entities/admin/types'

const router = useRouter()
const summary = ref<AdminSummary | null>(null)
const isLoading = ref(true)

onMounted(async () => {
  const response = await adminApi.getSummary()
  summary.value = response.data
  isLoading.value = false
})
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.push({ name: 'home' })">←</button>
      <span class="font-display text-lg text-ink">Админ-панель</span>
    </div>

    <div v-if="!isLoading && summary" class="flex flex-col gap-3 px-2">
      <button
        class="flex items-center justify-between rounded-2xl border border-hairline p-4 text-left"
        @click="router.push({ name: 'admin-reports' })"
      >
        <span class="text-sm text-ink">Жалобы на рассмотрении</span>
        <span class="text-lg font-semibold text-teal">{{ summary.pending_reports }}</span>
      </button>

      <button
        class="flex items-center justify-between rounded-2xl border border-hairline p-4 text-left"
        @click="router.push({ name: 'admin-shelter-verifications' })"
      >
        <span class="text-sm text-ink">Приюты ждут верификации</span>
        <span class="text-lg font-semibold text-teal">{{
          summary.pending_shelter_verifications
        }}</span>
      </button>

      <button
        class="flex items-center justify-between rounded-2xl border border-hairline p-4 text-left"
        @click="router.push({ name: 'admin-breeder-verifications' })"
      >
        <span class="text-sm text-ink">Заводчики ждут верификации</span>
        <span class="text-lg font-semibold text-teal">{{
          summary.pending_breeder_verifications
        }}</span>
      </button>

      <div class="flex items-center justify-between rounded-2xl border border-hairline p-4">
        <span class="text-sm text-ink">Открытые споры</span>
        <span class="text-lg font-semibold text-ink">{{ summary.open_disputes }}</span>
      </div>

      <button
        class="rounded-2xl border border-hairline p-4 text-left text-sm text-ink"
        @click="router.push({ name: 'admin-audit-log' })"
      >
        Журнал действий →
      </button>
    </div>
  </div>
</template>
