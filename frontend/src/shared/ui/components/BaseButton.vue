<script setup lang="ts">
import { computed } from 'vue'

/**
 * Кнопка по борду «A · Кнопки и действия» из docs/mockups/pen/elements-v2.pen.
 * Четыре вида, четыре размера и пять состояний, включая загрузку.
 */
const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'outline' | 'ghost' | 'danger'
    size?: 'xs' | 'sm' | 'md' | 'lg'
    type?: 'button' | 'submit'
    disabled?: boolean
    /** Показывает спиннер и блокирует нажатия, не меняя ширину кнопки. */
    loading?: boolean
    /** Во всю ширину контейнера — так кнопки стоят внизу мобильных экранов. */
    block?: boolean
  }>(),
  { variant: 'primary', size: 'md', type: 'button', disabled: false, loading: false, block: false },
)

const isLocked = computed(() => props.disabled || props.loading)

// Высоты из макета: L 54, M 44, S 36, XS 30.
const sizeClass = {
  lg: 'h-[54px] px-7 text-base gap-2',
  md: 'h-11 px-6 text-[15px] gap-2',
  sm: 'h-9 px-4 text-[13px] gap-1.5',
  xs: 'h-[30px] px-3 text-xs gap-1',
}

const variantClass = {
  primary: 'bg-accent text-accent-ink hover:bg-accent-hover active:bg-accent-pressed',
  danger:
    'bg-danger-fill text-white hover:bg-danger-fill-hover active:bg-danger-fill-pressed',
  outline:
    'border-[1.5px] border-accent text-accent-text hover:bg-accent-soft active:bg-accent-soft active:border-accent-pressed',
  ghost: 'text-ink hover:bg-surface-soft active:bg-hairline',
}

// Заблокированная кнопка в макете не полупрозрачная, а перекрашенная. Загрузка сюда не
// относится: кнопка со спиннером сохраняет свой цвет, просто не нажимается.
const lockedClass = {
  primary: 'bg-surface-soft text-ink-faint',
  danger: 'bg-surface-soft text-ink-faint',
  outline: 'border-[1.5px] border-hairline text-ink-faint',
  ghost: 'text-ink-faint',
}

const spinnerClass = computed(() =>
  props.variant === 'ghost' ? 'text-accent-text' : 'text-current',
)
</script>

<template>
  <button
    :type="type"
    :disabled="isLocked"
    :aria-busy="loading || undefined"
    class="inline-flex items-center justify-center rounded-full font-display font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed"
    :class="[
      sizeClass[size],
      disabled ? lockedClass[variant] : variantClass[variant],
      block && 'w-full',
    ]"
  >
    <svg
      v-if="loading"
      class="size-4 animate-spin"
      :class="spinnerClass"
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25" />
      <path
        d="M21 12a9 9 0 0 0-9-9"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
      />
    </svg>
    <slot />
  </button>
</template>
