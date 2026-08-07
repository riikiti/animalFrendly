<script setup lang="ts">
import { computed } from 'vue'
import { Check, Minus } from 'lucide-vue-next'

/** Чекбокс из секции «Переключатели и выбор» борда B: 24 пикселя, скругление 8. */
const props = withDefaults(
  defineProps<{
    modelValue: boolean
    disabled?: boolean
    error?: boolean
    /** Частичный выбор — когда отмечена часть вложенного списка. */
    indeterminate?: boolean
  }>(),
  { disabled: false, error: false, indeterminate: false },
)

defineEmits<{ 'update:modelValue': [value: boolean] }>()

const boxClass = computed(() => {
  if (props.disabled) return 'border-transparent bg-surface-soft text-ink-faint'
  if (props.modelValue || props.indeterminate) return 'border-transparent bg-accent text-accent-ink'
  if (props.error) return 'border-danger bg-surface'
  return 'border-hairline bg-surface'
})
</script>

<template>
  <label
    class="flex items-start gap-2.5 text-xs leading-relaxed text-ink-soft"
    :class="disabled && 'cursor-not-allowed text-ink-faint'"
  >
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      :indeterminate="indeterminate"
      class="peer sr-only"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span
      class="grid size-6 shrink-0 place-items-center rounded-lg border-[1.8px] transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent"
      :class="boxClass"
      aria-hidden="true"
    >
      <Minus v-if="indeterminate" class="size-4" stroke-width="3" />
      <Check v-else-if="modelValue" class="size-4" stroke-width="3" />
    </span>
    <span><slot /></span>
  </label>
</template>
