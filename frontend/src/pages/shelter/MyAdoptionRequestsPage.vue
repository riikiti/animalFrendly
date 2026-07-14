<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import * as shelterApi from '@/entities/shelter/api'
import type { AdoptionRequest, AdoptionRequestStatus } from '@/entities/shelter/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const router = useRouter()

const requests = ref<AdoptionRequest[]>([])
const isLoading = ref(true)

const statusLabels: Record<AdoptionRequestStatus, string> = {
  pending: 'На рассмотрении',
  approved: 'Одобрена',
  rejected: 'Отклонена',
  cancelled: 'Отменена',
}

onMounted(async () => {
  await refresh()
  isLoading.value = false
})

async function refresh(): Promise<void> {
  const response = await shelterApi.listMyAdoptionRequests()
  requests.value = response.data
}

async function cancel(requestId: string): Promise<void> {
  await shelterApi.cancelAdoptionRequest(requestId)
  await refresh()
}

async function openChat(requestId: string): Promise<void> {
  await router.push({ name: 'chat', params: { kind: 'adoption', id: requestId } })
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6">
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Мои заявки</span>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="requests.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Вы пока не подавали заявок на усыновление
      </p>

      <div
        v-for="request in requests"
        :key="request.id"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-3"
      >
        <div class="flex items-center justify-between">
          <span
            class="rounded-full px-2 py-1 text-xs font-semibold"
            :class="{
              'bg-accent/20 text-accent-ink': request.status === 'pending',
              'bg-teal-soft text-teal': request.status === 'approved',
              'bg-clay-soft text-clay':
                request.status === 'rejected' || request.status === 'cancelled',
            }"
          >
            {{ statusLabels[request.status] }}
          </span>
        </div>
        <p v-if="request.message" class="text-xs text-ink-soft">{{ request.message }}</p>

        <BaseButton
          v-if="request.status === 'approved'"
          variant="outline"
          @click="openChat(request.id)"
        >
          Написать в приют
        </BaseButton>
        <BaseButton
          v-else-if="request.status === 'pending'"
          variant="ghost"
          @click="cancel(request.id)"
        >
          Отменить заявку
        </BaseButton>
      </div>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
