<script setup lang="ts">
/**
 * Кирпичик скелетона из секции «Состояния загрузки» борда E.
 * Карточки и списки собираются из нескольких таких прямоугольников.
 */
withDefaults(
  defineProps<{
    /** line — строка текста, circle — аватар, block — фото или крупный блок. */
    variant?: 'line' | 'circle' | 'block'
    /** Ширина в любых единицах CSS: '180px', '60%', 'full'. */
    width?: string
    height?: string
  }>(),
  { variant: 'line' },
)
</script>

<template>
  <span
    class="block shrink-0 animate-pulse bg-surface-soft"
    :class="{
      'h-3 rounded-md': variant === 'line',
      'rounded-full': variant === 'circle',
      'rounded-2xl': variant === 'block',
    }"
    :style="{ width: width === 'full' ? '100%' : width, height }"
    aria-hidden="true"
  />
</template>
