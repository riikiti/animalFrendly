<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as moderationApi from '@/entities/moderation/api'
import type { Report } from '@/entities/moderation/types'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'

const router = useRouter()
const reports = ref<Report[]>([])
const isLoading = ref(true)
const error = ref('')

const manualBanUserId = ref('')
const manualBanError = ref('')

const reasonLabels: Record<string, string> = {
  spam: 'Спам',
  inappropriate: 'Неприемлемый контент',
  scam: 'Мошенничество',
  other: 'Другое',
}

const targetTypeLabels: Record<string, string> = {
  pet: 'Анкета питомца',
  listing: 'Листинг',
  user: 'Пользователь',
  message: 'Сообщение',
}

async function load(): Promise<void> {
  isLoading.value = true
  const response = await moderationApi.listReports()
  reports.value = response.data
  isLoading.value = false
}

onMounted(load)

async function review(reportId: string): Promise<void> {
  error.value = ''
  try {
    await moderationApi.reviewReport(reportId)
    await load()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  }
}

async function dismiss(reportId: string): Promise<void> {
  error.value = ''
  try {
    await moderationApi.dismissReport(reportId)
    await load()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  }
}

async function banFromReport(report: Report): Promise<void> {
  error.value = ''
  try {
    await moderationApi.banUser(report.target_id)
    await review(report.id)
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  }
}

async function banManually(): Promise<void> {
  manualBanError.value = ''
  try {
    await moderationApi.banUser(manualBanUserId.value)
    manualBanUserId.value = ''
  } catch (e) {
    manualBanError.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
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
      <span class="font-display text-lg text-ink">Жалобы</span>
    </div>

    <div class="flex flex-col gap-2 rounded-2xl border border-hairline p-4">
      <BaseInput
        v-model="manualBanUserId"
        label="Заблокировать по ID пользователя"
        placeholder="ID пользователя"
      />
      <p v-if="manualBanError" class="text-xs text-danger">{{ manualBanError }}</p>
      <BaseButton variant="outline" :disabled="!manualBanUserId" @click="banManually">
        Заблокировать
      </BaseButton>
    </div>

    <p v-if="error" class="px-2 text-xs text-danger">{{ error }}</p>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2">
      <p
        v-if="reports.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Нет жалоб на рассмотрении
      </p>

      <div
        v-for="report in reports"
        :key="report.id"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-4"
      >
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold text-ink">{{
            targetTypeLabels[report.target_type]
          }}</span>
          <span class="rounded-full bg-surface-soft px-2 py-1 text-xs text-ink-soft">
            {{ reasonLabels[report.reason] }}
          </span>
        </div>
        <p class="text-xs text-ink-faint">ID: {{ report.target_id }}</p>
        <p v-if="report.comment" class="text-sm text-ink-soft">{{ report.comment }}</p>

        <div class="flex gap-2">
          <BaseButton variant="outline" @click="dismiss(report.id)">Отклонить</BaseButton>
          <BaseButton @click="review(report.id)">Рассмотреть</BaseButton>
        </div>
        <button
          v-if="report.target_type === 'user'"
          class="text-xs font-semibold text-danger"
          @click="banFromReport(report)"
        >
          Заблокировать пользователя
        </button>
      </div>
    </div>
  </div>
</template>
