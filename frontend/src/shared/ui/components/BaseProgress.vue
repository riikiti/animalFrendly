<script setup lang="ts">
import { computed } from 'vue'

/** Полоса прогресса из секции «Пагинация и подгрузка» борда E. */
const props = withDefaults(
  defineProps<{
    value?: number
    max?: number
    label?: string
    /** Правая подпись, например «20 из 128». */
    valueLabel?: string
    /** Бесконечная полоса — когда доля неизвестна. */
    indeterminate?: boolean
  }>(),
  { value: 0, max: 100, indeterminate: false },
)

const percent = computed(() =>
  props.max > 0 ? Math.min(100, Math.max(0, (props.value / props.max) * 100)) : 0,
)
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="label || valueLabel" class="flex items-baseline justify-between text-[13px]">
      <span v-if="label" class="font-semibold text-ink">{{ label }}</span>
      <span v-if="valueLabel" class="font-semibold text-ink-faint">{{ valueLabel }}</span>
    </div>

    <div
      class="h-2 w-full overflow-hidden rounded-full bg-surface-soft"
      role="progressbar"
      :aria-valuenow="indeterminate ? undefined : value"
      :aria-valuemax="max"
      :aria-label="label"
    >
      <div
        class="h-full rounded-full bg-accent"
        :class="indeterminate && 'w-1/3 animate-[progress_1.2s_ease-in-out_infinite]'"
        :style="indeterminate ? undefined : { width: `${percent}%` }"
      />
    </div>
  </div>
</template>

<style>
@keyframes progress {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(300%);
  }
}
</style>
