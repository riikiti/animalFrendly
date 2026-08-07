<script setup lang="ts">
/**
 * Способ оплаты из компонента Payment Method макета маркетплейса: радио, значок,
 * название и приписка. Используется на экране оплаты и в настройках подписки.
 */
withDefaults(
  defineProps<{
    title: string
    description?: string
    selected?: boolean
    disabled?: boolean
  }>(),
  { selected: false, disabled: false },
)

defineEmits<{ select: [] }>()
</script>

<template>
  <button
    type="button"
    role="radio"
    :aria-checked="selected"
    :disabled="disabled"
    class="flex h-[66px] w-full items-center gap-3 rounded-2xl border-[1.5px] bg-surface px-3.5 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-60"
    :class="selected ? 'border-accent' : 'border-hairline hover:bg-surface-soft'"
    @click="$emit('select')"
  >
    <span
      class="grid size-[22px] shrink-0 place-items-center rounded-full border-2"
      :class="selected ? 'border-accent' : 'border-hairline'"
      aria-hidden="true"
    >
      <span v-if="selected" class="size-2.5 rounded-full bg-accent" />
    </span>

    <span
      v-if="$slots.icon"
      class="grid h-[30px] w-[42px] shrink-0 place-items-center rounded-lg bg-surface-soft text-ink-soft"
    >
      <slot name="icon" />
    </span>

    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
      <span class="truncate text-[13.5px] font-semibold text-ink">{{ title }}</span>
      <span v-if="description" class="truncate text-xs text-ink-faint">{{ description }}</span>
    </span>
  </button>
</template>
