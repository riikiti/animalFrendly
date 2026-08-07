<script setup lang="ts">
import { computed } from 'vue'
import { CircleAlert, Heart, Info, Trash2, TriangleAlert, X } from 'lucide-vue-next'
import type { Toast } from '@/shared/lib/toast'

/** Одно всплывающее уведомление из секции «Тосты» борда D. */
const props = defineProps<{ toast: Toast }>()

const emit = defineEmits<{ dismiss: []; action: [] }>()

// Успех в макете нарисован тёмной плашкой — он празднует, остальные просто сообщают.
const dark = computed(() => props.toast.tone === 'success' || props.toast.tone === 'compact')

const icons = {
  success: Heart,
  error: CircleAlert,
  info: Info,
  warning: TriangleAlert,
  compact: Trash2,
  loading: Info,
}

const iconCircleClass = {
  success: 'bg-teal/20 text-teal',
  error: 'bg-danger-soft text-danger',
  info: 'bg-info-soft text-info',
  warning: 'bg-gold-soft text-gold-text',
  compact: '',
  loading: '',
}

const actionClass = computed(() => {
  if (props.toast.tone === 'error') return 'text-danger'
  if (dark.value) return props.toast.tone === 'compact' ? 'text-accent-bright' : 'text-white'
  return 'text-accent-text'
})
</script>

<template>
  <div
    class="pointer-events-auto flex w-full items-center gap-3 shadow-md"
    :class="[
      dark ? 'bg-bezel text-white' : 'border border-hairline bg-surface text-ink',
      toast.tone === 'compact' ? 'rounded-full px-4 py-3' : 'rounded-[18px] p-4',
    ]"
    role="status"
  >
    <svg
      v-if="toast.tone === 'loading'"
      class="size-[22px] shrink-0 animate-spin text-accent"
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25" />
      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>

    <component
      :is="icons[toast.tone]"
      v-else-if="toast.tone === 'compact'"
      class="size-[17px] shrink-0"
      aria-hidden="true"
    />

    <span
      v-else
      class="grid size-9 shrink-0 place-items-center rounded-full"
      :class="iconCircleClass[toast.tone]"
    >
      <component :is="icons[toast.tone]" class="size-[18px]" aria-hidden="true" />
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
      <p class="truncate text-sm font-bold">{{ toast.title }}</p>
      <p
        v-if="toast.description"
        class="text-xs"
        :class="dark ? 'text-white/70' : 'text-ink-soft'"
      >
        {{ toast.description }}
      </p>
    </div>

    <button
      v-if="toast.actionLabel"
      type="button"
      class="shrink-0 text-[13px] font-bold"
      :class="actionClass"
      @click="emit('action')"
    >
      {{ toast.actionLabel }}
    </button>
    <button
      v-if="toast.tone !== 'loading' && (!toast.actionLabel || toast.sticky)"
      type="button"
      class="shrink-0 opacity-60 transition-opacity hover:opacity-100"
      aria-label="Закрыть уведомление"
      @click="emit('dismiss')"
    >
      <X class="size-4" />
    </button>
  </div>
</template>
