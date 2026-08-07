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
    class="relative flex items-start gap-2.5 text-xs leading-relaxed text-ink-soft"
    :class="disabled && 'cursor-not-allowed text-ink-faint'"
  >
    <!-- Настоящий input лежит поверх нарисованной клетки и прозрачен: так по нему
    попадают и мышь, и автотесты, а рисунок остаётся наш. Прятать его в sr-only нельзя —
    клик уходит в подпись. -->
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      :indeterminate="indeterminate"
      class="peer absolute top-0 left-0 size-6 cursor-pointer opacity-0 disabled:cursor-not-allowed"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span
      class="pointer-events-none grid size-6 shrink-0 place-items-center rounded-lg border-[1.8px] transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent"
      :class="boxClass"
      aria-hidden="true"
    >
      <Minus v-if="indeterminate" class="size-4" stroke-width="3" />
      <Check v-else-if="modelValue" class="size-4" stroke-width="3" />
    </span>
    <span><slot /></span>
  </label>
</template>
