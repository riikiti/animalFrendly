<script setup lang="ts">
import { Search, X } from 'lucide-vue-next'
import BaseField from './BaseField.vue'

/** Поле поиска из секции «Типы полей» борда B: таблетка на утопленной заливке. */
withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    placeholder?: string
    disabled?: boolean
  }>(),
  { disabled: false, placeholder: 'Поиск' },
)

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <BaseField :label="label" :disabled="disabled" variant="pill" tone="sunk">
    <template #lead>
      <Search class="size-[19px] shrink-0 text-ink-faint" aria-hidden="true" />
    </template>

    <input
      type="search"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      class="w-full min-w-0 bg-transparent text-[15px] font-medium text-ink outline-none placeholder:text-ink-faint [&::-webkit-search-cancel-button]:hidden"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />

    <template #trail>
      <button
        v-if="modelValue"
        type="button"
        class="shrink-0 text-ink-faint transition-colors hover:text-ink-soft"
        aria-label="Очистить поиск"
        @click="$emit('update:modelValue', '')"
      >
        <X class="size-[18px]" />
      </button>
    </template>
  </BaseField>
</template>
