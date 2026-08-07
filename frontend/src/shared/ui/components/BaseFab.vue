<script setup lang="ts">
/**
 * Плавающая кнопка действия из секции «Иконочные кнопки и FAB» борда A.
 * Позиционирование оставлено экрану — компонент отвечает только за вид.
 */
withDefaults(
  defineProps<{
    label: string
    /** Расширенный вид: иконка плюс подпись. Обычный — только иконка. */
    extended?: boolean
    type?: 'button' | 'submit'
    disabled?: boolean
  }>(),
  { extended: false, type: 'button', disabled: false },
)
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :aria-label="extended ? undefined : label"
    class="inline-flex items-center justify-center gap-2 rounded-full bg-accent font-display font-semibold text-accent-ink shadow-lg transition-colors hover:bg-accent-hover active:bg-accent-pressed focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:bg-surface-soft disabled:text-ink-faint disabled:shadow-none"
    :class="extended ? 'h-14 px-6 text-[15px]' : 'size-14'"
  >
    <slot />
    <span v-if="extended">{{ label }}</span>
  </button>
</template>
