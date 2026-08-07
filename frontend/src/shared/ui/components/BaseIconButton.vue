<script setup lang="ts">
/**
 * Иконочная кнопка по секции «Иконочные кнопки и FAB» борда A.
 * Круглая — для мобильных действий поверх контента, квадратная — для десктопных панелей.
 */
withDefaults(
  defineProps<{
    /** Обязательна: внутри только иконка, читалке нужно название действия. */
    label: string
    shape?: 'circle' | 'square'
    size?: 'sm' | 'md' | 'lg'
    tone?: 'neutral' | 'active' | 'danger' | 'success'
    type?: 'button' | 'submit'
    disabled?: boolean
    /** Тень — когда кнопка лежит поверх фотографии, а не в ряду панели. */
    elevated?: boolean
    /** Счётчик в углу: непрочитанные, количество в корзине. */
    badge?: number | string
  }>(),
  {
    shape: 'circle',
    size: 'md',
    tone: 'neutral',
    type: 'button',
    disabled: false,
    elevated: false,
    badge: undefined,
  },
)

const sizeClass = {
  sm: 'size-9',
  md: 'size-12',
  lg: 'size-[60px]',
}

const toneClass = {
  neutral: 'bg-surface text-ink-soft border border-hairline hover:bg-bg active:bg-surface-soft',
  active: 'bg-accent text-accent-ink hover:bg-accent-hover active:bg-accent-pressed',
  danger: 'bg-danger-soft text-danger hover:brightness-95 active:brightness-90',
  success: 'bg-teal-soft text-teal hover:brightness-95 active:brightness-90',
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :aria-label="label"
    :title="label"
    class="relative inline-flex shrink-0 items-center justify-center transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:border-transparent disabled:bg-surface-soft disabled:text-ink-faint disabled:shadow-none"
    :class="[
      sizeClass[size],
      toneClass[tone],
      shape === 'circle' ? 'rounded-full' : 'rounded-xl',
      elevated && 'shadow-sm',
    ]"
  >
    <slot />
    <span
      v-if="badge !== undefined"
      class="absolute -top-1 -right-1 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-danger-fill px-1 text-[10px] font-semibold text-white"
      aria-hidden="true"
      >{{ badge }}</span
    >
  </button>
</template>
