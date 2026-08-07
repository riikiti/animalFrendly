<script setup lang="ts">
import { computed } from 'vue'
import BaseField from './BaseField.vue'

/** Многострочное поле из секции «Типы полей» борда B, со счётчиком символов. */
const props = withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
    rows?: number
    /** Показывает счётчик и подсвечивает перебор. */
    maxlength?: number
  }>(),
  { rows: 4, disabled: false },
)

defineEmits<{ 'update:modelValue': [value: string] }>()

const overflow = computed(() => props.maxlength !== undefined && props.modelValue.length > props.maxlength)
</script>

<template>
  <BaseField :label="label" :error="error" :hint="hint" :disabled="disabled" grow>
    <span class="flex w-full flex-col gap-2">
      <textarea
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :rows="rows"
        class="w-full resize-none bg-transparent text-sm font-medium text-ink outline-none placeholder:text-ink-faint disabled:cursor-not-allowed disabled:text-ink-faint"
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
      />
      <span
        v-if="maxlength !== undefined"
        class="self-end text-[11px] font-semibold"
        :class="overflow ? 'text-danger' : 'text-ink-faint'"
        >{{ modelValue.length }} / {{ maxlength }}</span
      >
    </span>
  </BaseField>
</template>
