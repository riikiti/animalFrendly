<script setup lang="ts">
/**
 * Пустое состояние и экран ошибки из секций «Пустые состояния» и «Ошибки» борда E —
 * в макете это одна и та же конструкция, отличается только цвет кружка.
 */
withDefaults(
  defineProps<{
    title: string
    description?: string
    tone?: 'accent' | 'teal' | 'gold' | 'info' | 'danger' | 'neutral'
    /** Без рамки — когда состояние занимает весь экран, а не карточку в списке. */
    bare?: boolean
  }>(),
  { tone: 'accent', bare: false },
)

const toneClass = {
  accent: 'bg-accent-soft text-accent-text',
  teal: 'bg-teal-soft text-teal-text',
  gold: 'bg-gold-soft text-gold-text',
  info: 'bg-info-soft text-info',
  danger: 'bg-danger-soft text-danger',
  neutral: 'bg-surface-soft text-ink-soft',
}
</script>

<template>
  <div
    class="flex flex-col items-center justify-center gap-3 px-6 py-9 text-center"
    :class="!bare && 'rounded-card border border-hairline bg-surface'"
  >
    <span
      v-if="$slots.icon"
      class="grid size-[74px] place-items-center rounded-full"
      :class="toneClass[tone]"
    >
      <slot name="icon" />
    </span>
    <h3 class="font-display text-lg font-bold text-ink">{{ title }}</h3>
    <p v-if="description" class="max-w-xs text-[13.5px] leading-relaxed text-ink-soft">
      {{ description }}
    </p>
    <div v-if="$slots.actions" class="mt-1 flex flex-col items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
