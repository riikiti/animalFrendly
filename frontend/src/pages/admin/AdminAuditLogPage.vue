<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as adminApi from '@/entities/admin/api'
import type { AuditLogEntry } from '@/entities/admin/types'

const router = useRouter()
const entries = ref<AuditLogEntry[]>([])
const isLoading = ref(true)

onMounted(async () => {
  const response = await adminApi.getAuditLog()
  entries.value = response.data
  isLoading.value = false
})

function formatDate(value: string): string {
  return new Date(value).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
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
      <span class="font-display text-xl font-bold text-ink">Журнал действий</span>
    </div>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2">
      <p
        v-if="entries.length === 0"
        class="rounded-card bg-surface-soft p-6 text-center text-sm text-ink-soft"
      >
        Пока нет записей
      </p>

      <div
        v-for="entry in entries"
        :key="entry.id"
        class="flex flex-col gap-1 rounded-card border border-hairline bg-surface p-4 lg:flex-row lg:items-center lg:justify-between lg:gap-6"
      >
        <span class="text-sm font-semibold text-ink">{{ entry.action }}</span>
        <span class="text-xs text-ink-faint">
          {{ entry.entity_type }} · {{ entry.entity_id }} · {{ formatDate(entry.created_at) }}
        </span>
      </div>
    </div>
  </div>
</template>
