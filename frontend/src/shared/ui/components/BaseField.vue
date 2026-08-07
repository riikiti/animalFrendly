<script setup lang="ts">
/**
 * Оболочка поля ввода по борду «B · Поля ввода и формы»: подпись сверху, рамка,
 * место под ведущую и замыкающую иконку, подсказка или ошибка снизу.
 * Сам контрол приходит слотом — так на одной оболочке живут input, select и textarea.
 */
withDefaults(
  defineProps<{
    label?: string
    error?: string
    hint?: string
    disabled?: boolean
    /** box — прямоугольное поле формы, pill — скруглённое поле поиска. */
    variant?: 'box' | 'pill'
    /** sunk — утопленная заливка без рамки, как у поиска. */
    tone?: 'surface' | 'sunk'
    /** Высота по содержимому — для многострочного поля. */
    grow?: boolean
  }>(),
  { variant: 'box', tone: 'surface', disabled: false, grow: false },
)
</script>

<template>
  <label class="flex flex-col gap-1.5">
    <span v-if="label" class="text-xs font-semibold text-ink-soft">{{ label }}</span>
    <span
      class="flex items-center gap-2.5 border-[1.5px] px-4 transition-colors"
      :class="[
        variant === 'pill' ? 'rounded-full' : 'rounded-[14px]',
        grow ? 'py-3.5' : variant === 'pill' ? 'h-12' : 'h-13',
        tone === 'sunk' ? 'border-transparent bg-surface-soft' : 'border-hairline bg-surface',
        disabled
          ? 'cursor-not-allowed border-transparent bg-surface-soft'
          : 'focus-within:border-accent',
        error && !disabled && 'border-danger',
      ]"
    >
      <slot name="lead" />
      <slot />
      <slot name="trail" />
    </span>
    <span v-if="error" class="text-xs text-danger">{{ error }}</span>
    <span v-else-if="hint" class="text-xs text-ink-faint">{{ hint }}</span>
  </label>
</template>
