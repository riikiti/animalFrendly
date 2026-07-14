<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'outline' | 'ghost'
    type?: 'button' | 'submit'
    disabled?: boolean
  }>(),
  { variant: 'primary', type: 'button', disabled: false },
)
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="inline-flex items-center justify-center gap-1.5 rounded-full px-5 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50"
    :class="{
      'bg-accent text-accent-ink hover:brightness-105': variant === 'primary',
      'border border-hairline text-ink hover:bg-surface-soft': variant === 'outline',
      'bg-surface-soft text-ink hover:bg-hairline': variant === 'ghost',
    }"
  >
    <slot />
  </button>
</template>
