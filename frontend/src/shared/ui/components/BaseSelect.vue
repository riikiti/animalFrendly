<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import BaseField from './BaseField.vue'

/**
 * Выпадающий список из секции «Типы полей» борда B. Внутри нативный select: на телефоне
 * он открывает системный выбор, который заметно удобнее и доступнее рисованного списка.
 */
withDefaults(
  defineProps<{
    modelValue: string | number | null
    options: { value: string | number; label: string }[]
    label?: string
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
  }>(),
  { disabled: false, placeholder: 'Не выбрано' },
)

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <BaseField :label="label" :error="error" :hint="hint" :disabled="disabled">
    <template v-if="$slots.lead" #lead>
      <slot name="lead" />
    </template>

    <select
      :value="modelValue ?? ''"
      :disabled="disabled"
      class="w-full min-w-0 appearance-none bg-transparent text-[15px] font-medium outline-none disabled:cursor-not-allowed disabled:text-ink-faint"
      :class="modelValue === null || modelValue === '' ? 'text-ink-faint' : 'text-ink'"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="">{{ placeholder }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>

    <template #trail>
      <ChevronDown class="size-[18px] shrink-0 text-ink-faint" aria-hidden="true" />
    </template>
  </BaseField>
</template>
