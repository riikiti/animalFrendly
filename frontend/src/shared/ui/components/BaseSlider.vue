<script setup lang="ts">
import { computed } from 'vue'

/**
 * Одиночный слайдер из секции «Слайдеры и счётчики» борда B — например «возраст до 5 лет».
 * Внутри нативный input[type=range]: он сам умеет клавиатуру и жесты, остаётся только
 * перекрасить дорожку и бегунок.
 */
const props = withDefaults(
  defineProps<{
    modelValue: number
    min?: number
    max?: number
    step?: number
    label?: string
    /** Подпись текущего значения, например «5 лет» или «до 30 км». */
    valueLabel?: string
    /** Подписи по краям дорожки. */
    minLabel?: string
    maxLabel?: string
    disabled?: boolean
  }>(),
  { min: 0, max: 100, step: 1, disabled: false },
)

defineEmits<{ 'update:modelValue': [value: number] }>()

const progress = computed(() => {
  const span = props.max - props.min
  if (span <= 0) return 0
  return Math.min(100, Math.max(0, ((props.modelValue - props.min) / span) * 100))
})
</script>

<template>
  <div class="flex flex-col gap-2">
    <div v-if="label || valueLabel" class="flex items-baseline justify-between">
      <span v-if="label" class="text-xs font-semibold text-ink-soft">{{ label }}</span>
      <span v-if="valueLabel" class="text-[13px] font-semibold text-accent-text">{{
        valueLabel
      }}</span>
    </div>

    <input
      type="range"
      class="slider"
      :value="modelValue"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :aria-label="label"
      :style="{ '--progress': `${progress}%` }"
      @input="$emit('update:modelValue', Number(($event.target as HTMLInputElement).value))"
    />

    <div v-if="minLabel || maxLabel" class="flex justify-between text-[11px] text-ink-faint">
      <span>{{ minLabel }}</span>
      <span>{{ maxLabel }}</span>
    </div>
  </div>
</template>

<style scoped>
.slider {
  --track: 6px;
  --thumb: 22px;
  width: 100%;
  height: var(--thumb);
  appearance: none;
  background: transparent;
  outline: none;
}

.slider:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* Заполненная часть дорожки рисуется градиентом до текущего значения. */
.slider::-webkit-slider-runnable-track {
  height: var(--track);
  border-radius: 9999px;
  background: linear-gradient(
    to right,
    var(--accent) var(--progress),
    var(--surface-soft) var(--progress)
  );
}

.slider::-moz-range-track {
  height: var(--track);
  border-radius: 9999px;
  background: var(--surface-soft);
}

.slider::-moz-range-progress {
  height: var(--track);
  border-radius: 9999px;
  background: var(--accent);
}

.slider::-webkit-slider-thumb {
  appearance: none;
  width: var(--thumb);
  height: var(--thumb);
  margin-top: calc((var(--track) - var(--thumb)) / 2);
  border: 2px solid var(--accent);
  border-radius: 9999px;
  background: var(--surface);
  box-shadow: var(--elev-sm);
}

.slider::-moz-range-thumb {
  width: var(--thumb);
  height: var(--thumb);
  border: 2px solid var(--accent);
  border-radius: 9999px;
  background: var(--surface);
  box-shadow: var(--elev-sm);
}

.slider:focus-visible::-webkit-slider-thumb {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.slider:focus-visible::-moz-range-thumb {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}
</style>
