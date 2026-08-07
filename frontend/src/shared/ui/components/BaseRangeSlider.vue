<script setup lang="ts">
import { computed } from 'vue'

/**
 * Слайдер-диапазон из секции «Слайдеры и счётчики» борда B — «расстояние от и до».
 * Внутри два нативных input[type=range]: клавиатура и жесты достаются бесплатно,
 * а перекрытие решается тем, что события ловят только сами бегунки.
 */
const props = withDefaults(
  defineProps<{
    /** Пара значений [от, до]. */
    modelValue: [number, number]
    min?: number
    max?: number
    step?: number
    label?: string
    /** Подпись текущего диапазона, например «5 — 30 км». */
    valueLabel?: string
    minLabel?: string
    maxLabel?: string
    disabled?: boolean
  }>(),
  { min: 0, max: 100, step: 1, disabled: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: [number, number]] }>()

const span = computed(() => props.max - props.min || 1)
const toPercent = (value: number) => ((value - props.min) / span.value) * 100

const left = computed(() => toPercent(props.modelValue[0]))
const right = computed(() => toPercent(props.modelValue[1]))

// Бегунки не должны проходить друг сквозь друга — нижний упирается в верхний и наоборот.
const setLow = (value: number) => emit('update:modelValue', [Math.min(value, props.modelValue[1]), props.modelValue[1]])
const setHigh = (value: number) => emit('update:modelValue', [props.modelValue[0], Math.max(value, props.modelValue[0])])
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="label || valueLabel" class="flex items-baseline justify-between">
      <span v-if="label" class="text-xs font-semibold text-ink-soft">{{ label }}</span>
      <span v-if="valueLabel" class="text-[13px] font-semibold text-accent-text">{{
        valueLabel
      }}</span>
    </div>

    <div class="relative h-[22px]" :class="disabled && 'opacity-50'">
      <span class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-surface-soft" />
      <span
        class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-accent"
        :style="{ left: `${left}%`, right: `${100 - right}%` }"
      />

      <input
        type="range"
        class="range"
        :value="modelValue[0]"
        :min="min"
        :max="max"
        :step="step"
        :disabled="disabled"
        :aria-label="label ? `${label}: от` : 'От'"
        @input="setLow(Number(($event.target as HTMLInputElement).value))"
      />
      <input
        type="range"
        class="range"
        :value="modelValue[1]"
        :min="min"
        :max="max"
        :step="step"
        :disabled="disabled"
        :aria-label="label ? `${label}: до` : 'До'"
        @input="setHigh(Number(($event.target as HTMLInputElement).value))"
      />
    </div>

    <div v-if="minLabel || maxLabel" class="flex justify-between text-[11px] text-ink-faint">
      <span>{{ minLabel }}</span>
      <span>{{ maxLabel }}</span>
    </div>
  </div>
</template>

<style scoped>
.range {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  appearance: none;
  background: transparent;
  outline: none;
  /* Дорожка нарисована отдельными элементами, поэтому сам input прозрачен для мыши —
     ловят события только бегунки, иначе верхний input перекрыл бы нижний целиком. */
  pointer-events: none;
}

.range::-webkit-slider-runnable-track {
  height: 100%;
  background: transparent;
}

.range::-moz-range-track {
  height: 100%;
  background: transparent;
}

.range::-webkit-slider-thumb {
  pointer-events: auto;
  appearance: none;
  width: 22px;
  height: 22px;
  border: 2px solid var(--accent);
  border-radius: 9999px;
  background: var(--surface);
  box-shadow: var(--elev-sm);
  cursor: grab;
}

.range::-moz-range-thumb {
  pointer-events: auto;
  width: 22px;
  height: 22px;
  border: 2px solid var(--accent);
  border-radius: 9999px;
  background: var(--surface);
  box-shadow: var(--elev-sm);
  cursor: grab;
}

.range:focus-visible::-webkit-slider-thumb {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.range:focus-visible::-moz-range-thumb {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}
</style>
