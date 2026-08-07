<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import * as shelterApi from '@/entities/shelter/api'
import type { AdoptionRequest, AdoptionRequestStatus } from '@/entities/shelter/types'
import * as moderationApi from '@/entities/moderation/api'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const router = useRouter()

const requests = ref<AdoptionRequest[]>([])
const isLoading = ref(true)

const reviewFormOpenId = ref<string | null>(null)
const reviewedRequestIds = ref(new Set<string>())
const reviewRating = ref(5)
const reviewComment = ref('')
const reviewErrors = reactive<Record<string, string>>({})

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

function openReviewForm(requestId: string): void {
  reviewFormOpenId.value = requestId
  reviewRating.value = 5
  reviewComment.value = ''
  delete reviewErrors[requestId]
}

async function submitReview(requestId: string): Promise<void> {
  delete reviewErrors[requestId]
  try {
    await moderationApi.submitReview({
      adoption_request_id: requestId,
      rating: reviewRating.value,
      comment: reviewComment.value || null,
    })
    reviewedRequestIds.value.add(requestId)
    reviewFormOpenId.value = null
  } catch (e) {
    reviewErrors[requestId] = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-xl font-bold text-ink">Мои заявки</span>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-3 pb-4">
      <p
        v-if="requests.length === 0"
        class="rounded-card bg-surface-soft p-6 text-center text-sm text-ink-soft"
      >
        Вы пока не подавали заявок на усыновление
      </p>

      <div
        v-for="request in requests"
        :key="request.id"
        class="flex flex-col gap-2 rounded-card border border-hairline bg-surface p-3"
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

        <template v-if="request.status === 'approved' && !reviewedRequestIds.has(request.id)">
          <button
            v-if="reviewFormOpenId !== request.id"
            type="button"
            class="text-xs font-bold text-accent-text"
            @click="openReviewForm(request.id)"
          >
            Оставить отзыв о приюте
          </button>
          <div v-else class="flex flex-col gap-2">
            <div class="flex gap-1">
              <button
                v-for="star in [1, 2, 3, 4, 5]"
                :key="star"
                type="button"
                class="text-2xl"
                :class="star <= reviewRating ? 'text-accent' : 'text-hairline'"
                @click="reviewRating = star"
              >
                ★
              </button>
            </div>
            <textarea
              v-model="reviewComment"
              rows="2"
              placeholder="Комментарий (необязательно)"
              class="rounded-xl bg-surface-soft px-3 py-2 text-sm text-ink outline-none"
            ></textarea>
            <p v-if="reviewErrors[request.id]" class="text-xs text-danger">
              {{ reviewErrors[request.id] }}
            </p>
            <div class="flex gap-2">
              <BaseButton @click="submitReview(request.id)">Отправить</BaseButton>
              <BaseButton variant="ghost" @click="reviewFormOpenId = null">Отмена</BaseButton>
            </div>
          </div>
        </template>
        <p v-else-if="reviewedRequestIds.has(request.id)" class="text-xs text-teal">
          Спасибо за отзыв!
        </p>
      </div>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
