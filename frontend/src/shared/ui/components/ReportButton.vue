<script setup lang="ts">
import { ref } from 'vue'
import * as moderationApi from '@/entities/moderation/api'
import type { ReportReason, ReportTargetType } from '@/entities/moderation/types'
import { ApiError } from '@/shared/api/http'

const props = defineProps<{ targetType: ReportTargetType; targetId: string }>()

const isOpen = ref(false)
const reason = ref<ReportReason>('spam')
const comment = ref('')
const isSubmitting = ref(false)
const submitted = ref(false)
const error = ref('')

const reasons: { value: ReportReason; title: string }[] = [
  { value: 'spam', title: 'Спам' },
  { value: 'inappropriate', title: 'Неприемлемо' },
  { value: 'scam', title: 'Мошенничество' },
  { value: 'other', title: 'Другое' },
]

function open(): void {
  isOpen.value = true
  submitted.value = false
  error.value = ''
  comment.value = ''
}

function close(): void {
  isOpen.value = false
}

async function submit(): Promise<void> {
  isSubmitting.value = true
  error.value = ''

  try {
    await moderationApi.submitReport({
      target_type: props.targetType,
      target_id: props.targetId,
      reason: reason.value,
      comment: comment.value || null,
    })
    submitted.value = true
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <button type="button" class="text-xs text-ink-faint" @click="open">⚑ Пожаловаться</button>

  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
    @click.self="close"
  >
    <div class="flex w-full max-w-sm flex-col gap-3 rounded-t-3xl bg-surface p-6 pb-8">
      <div class="flex items-center justify-between">
        <span class="font-display text-lg text-ink">Пожаловаться</span>
        <button class="text-sm text-ink-faint" @click="close">✕</button>
      </div>

      <template v-if="!submitted">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="r in reasons"
            :key="r.value"
            type="button"
            class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
            :class="reason === r.value ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
            @click="reason = r.value"
          >
            {{ r.title }}
          </button>
        </div>
        <textarea
          v-model="comment"
          rows="3"
          placeholder="Комментарий (необязательно)"
          class="rounded-xl bg-surface-soft px-3 py-2 text-sm text-ink outline-none"
        ></textarea>
        <p v-if="error" class="text-xs text-danger">{{ error }}</p>
        <button
          class="inline-flex items-center justify-center rounded-full bg-accent px-5 py-2.5 text-sm font-semibold text-accent-ink disabled:opacity-50"
          :disabled="isSubmitting"
          @click="submit"
        >
          {{ isSubmitting ? 'Отправляем…' : 'Отправить жалобу' }}
        </button>
      </template>
      <p v-else class="text-sm text-teal">Спасибо, жалоба отправлена.</p>
    </div>
  </div>
</template>
