<script setup lang="ts">
/**
 * Строка суммы из компонента Money Row макета маркетплейса — из них собраны расчёт
 * сделки, выплата продавцу и удержание комиссии.
 */
withDefaults(
  defineProps<{
    label: string
    value: string
    /** total — итог крупнее и жирнее, muted — служебная строка вроде комиссии. */
    variant?: 'default' | 'total' | 'muted'
    /** Отрицательная сумма — комиссия площадки, красится в цвет предупреждения. */
    negative?: boolean
  }>(),
  { variant: 'default', negative: false },
)
</script>

<template>
  <div class="flex items-center justify-between gap-3">
    <span
      class="text-[13.5px]"
      :class="variant === 'total' ? 'font-semibold text-ink' : 'text-ink-soft'"
      >{{ label }}</span
    >
    <span
      class="font-bold"
      :class="[
        variant === 'total' ? 'text-lg' : 'text-[13.5px]',
        negative ? 'text-danger-text' : variant === 'muted' ? 'text-ink-soft' : 'text-ink',
      ]"
      >{{ value }}</span
    >
  </div>
</template>
