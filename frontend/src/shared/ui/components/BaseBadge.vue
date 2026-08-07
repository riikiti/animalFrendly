<script setup lang="ts">
/**
 * Бейдж из секций «Бейджи верификации» и «Бейджи статусов» борда C.
 * Тон выбирает вызывающий код — привязка статусов домена к цветам живёт рядом с
 * сущностями, а не здесь.
 */
withDefaults(
  defineProps<{
    tone?: 'teal' | 'gold' | 'info' | 'danger' | 'accent' | 'neutral'
    /** Заливка вместо мягкой подложки — для акцентных статусов вроде «Пристроен». */
    solid?: boolean
    size?: 'sm' | 'md'
  }>(),
  { tone: 'teal', solid: false, size: 'sm' },
)

const softClass = {
  teal: 'bg-teal-soft text-teal-text',
  gold: 'bg-gold-soft text-gold-text',
  info: 'bg-info-soft text-info',
  danger: 'bg-danger-soft text-danger-text',
  accent: 'bg-accent-soft text-accent-text',
  neutral: 'bg-surface-soft text-ink-soft',
}

const solidClass = {
  teal: 'bg-teal text-white',
  gold: 'bg-gold text-bezel',
  info: 'bg-info text-white',
  danger: 'bg-danger-fill text-white',
  accent: 'bg-accent text-accent-ink',
  neutral: 'bg-bezel text-bg',
}
</script>

<template>
  <span
    class="inline-flex shrink-0 items-center gap-1.5 rounded-full font-semibold"
    :class="[
      size === 'md' ? 'px-3.5 py-2 text-sm' : 'px-2.5 py-1 text-xs',
      solid ? solidClass[tone] : softClass[tone],
    ]"
  >
    <slot name="icon" />
    <slot />
  </span>
</template>
