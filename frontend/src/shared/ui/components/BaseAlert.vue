<script setup lang="ts">
import { CircleCheck, CircleX, Info, TriangleAlert } from 'lucide-vue-next'

/**
 * Инлайн-алерт из секции «Инлайн-алерты и баннеры» борда D — сообщение внутри экрана,
 * в отличие от всплывающего тоста.
 */
withDefaults(
  defineProps<{
    tone?: 'success' | 'warning' | 'error' | 'info'
    title?: string
  }>(),
  { tone: 'info' },
)

const toneClass = {
  success: 'bg-teal-soft text-teal-text',
  warning: 'bg-gold-soft text-gold-text',
  error: 'bg-danger-soft text-danger-text',
  info: 'bg-info-soft text-info',
}

const icons = {
  success: CircleCheck,
  warning: TriangleAlert,
  error: CircleX,
  info: Info,
}
</script>

<template>
  <div class="flex gap-3 rounded-2xl p-3.5" :class="toneClass[tone]" role="status">
    <component :is="icons[tone]" class="mt-px size-[19px] shrink-0" aria-hidden="true" />
    <div class="flex flex-col gap-1">
      <p v-if="title" class="text-sm font-bold">{{ title }}</p>
      <p class="text-[12.5px] leading-relaxed opacity-90"><slot /></p>
      <slot name="action" />
    </div>
  </div>
</template>
