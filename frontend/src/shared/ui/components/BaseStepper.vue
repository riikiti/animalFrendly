<script setup lang="ts">
import { computed } from 'vue'
import { Minus, Plus } from 'lucide-vue-next'

/** Счётчик из секции «Слайдеры и счётчики» борда B. */
const props = withDefaults(
  defineProps<{
    modelValue: number
    min?: number
    max?: number
    step?: number
    ariaLabel?: string
    disabled?: boolean
  }>(),
  { min: 0, max: 99, step: 1, disabled: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: number] }>()

const canDecrease = computed(() => !props.disabled && props.modelValue - props.step >= props.min)
const canIncrease = computed(() => !props.disabled && props.modelValue + props.step <= props.max)

const change = (delta: number) => emit('update:modelValue', props.modelValue + delta * props.step)
</script>

<template>
  <div
    class="inline-flex h-11 w-fit items-center gap-1 rounded-full border-[1.5px] border-hairline bg-surface p-1"
    :class="disabled && 'border-transparent bg-surface-soft'"
  >
    <button
      type="button"
      class="grid size-9 place-items-center rounded-full bg-surface-soft text-ink transition-colors hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:text-ink-faint"
      :disabled="!canDecrease"
      :aria-label="ariaLabel ? `${ariaLabel}: меньше` : 'Уменьшить'"
      @click="change(-1)"
    >
      <Minus class="size-4" stroke-width="2.5" />
    </button>

    <output class="w-11 text-center text-[15px] font-semibold text-ink" :aria-label="ariaLabel">{{
      modelValue
    }}</output>

    <button
      type="button"
      class="grid size-9 place-items-center rounded-full bg-accent-soft text-accent-text transition-colors hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:bg-surface-soft disabled:text-ink-faint"
      :disabled="!canIncrease"
      :aria-label="ariaLabel ? `${ariaLabel}: больше` : 'Увеличить'"
      @click="change(1)"
    >
      <Plus class="size-4" stroke-width="2.5" />
    </button>
  </div>
</template>
