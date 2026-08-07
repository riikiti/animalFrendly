<script setup lang="ts">
import { computed } from 'vue'

/** Радиокнопка из секции «Переключатели и выбор» борда B. */
const props = withDefaults(
  defineProps<{
    modelValue: string | number | null
    /** Значение, которое эта кнопка выставляет группе. */
    value: string | number
    name: string
    disabled?: boolean
  }>(),
  { disabled: false },
)

defineEmits<{ 'update:modelValue': [value: string | number] }>()

const checked = computed(() => props.modelValue === props.value)
</script>

<template>
  <label
    class="relative flex items-start gap-2.5 text-sm text-ink"
    :class="disabled && 'cursor-not-allowed text-ink-faint'"
  >
    <!-- Прозрачный input поверх нарисованного кружка — см. комментарий в BaseCheckbox. -->
    <input
      type="radio"
      :name="name"
      :value="value"
      :checked="checked"
      :disabled="disabled"
      class="peer absolute top-0 left-0 size-6 cursor-pointer opacity-0 disabled:cursor-not-allowed"
      @change="$emit('update:modelValue', value)"
    />
    <span
      class="pointer-events-none grid size-6 shrink-0 place-items-center rounded-full border-2 transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent"
      :class="
        disabled
          ? 'border-transparent bg-surface-soft'
          : checked
            ? 'border-accent bg-surface'
            : 'border-hairline bg-surface'
      "
      aria-hidden="true"
    >
      <span v-if="checked && !disabled" class="size-2.5 rounded-full bg-accent" />
    </span>
    <span><slot /></span>
  </label>
</template>
