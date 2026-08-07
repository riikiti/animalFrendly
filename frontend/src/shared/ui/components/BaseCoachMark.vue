<script setup lang="ts">
/**
 * Подсказка-обучалка из секции «Тултипы и поповеры» борда D: коралловая карточка,
 * которая объясняет новый жест или раздел и закрывается кнопкой «Понятно».
 */
withDefaults(defineProps<{ title: string; actionLabel?: string }>(), { actionLabel: 'Понятно' })

defineEmits<{ dismiss: [] }>()
</script>

<template>
  <div class="flex items-start gap-3 rounded-[18px] bg-accent p-4 text-accent-ink shadow-md">
    <span v-if="$slots.icon" class="mt-0.5 shrink-0"><slot name="icon" /></span>

    <div class="flex flex-1 flex-col gap-1">
      <p class="font-display text-sm font-bold">{{ title }}</p>
      <p class="text-[12.5px] leading-relaxed opacity-90"><slot /></p>
    </div>

    <button
      type="button"
      class="shrink-0 rounded-full bg-surface px-3.5 py-2 text-xs font-bold text-accent-text transition hover:brightness-95"
      @click="$emit('dismiss')"
    >
      {{ actionLabel }}
    </button>
  </div>
</template>
