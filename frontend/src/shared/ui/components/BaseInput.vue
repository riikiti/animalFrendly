<script setup lang="ts">
defineProps<{
  modelValue: string
  label: string
  type?: string
  placeholder?: string
  error?: string
}>()

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <label class="flex flex-col gap-1.5">
    <span class="text-xs font-semibold text-ink-soft">{{ label }}</span>
    <input
      :type="type ?? 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      class="rounded-xl bg-surface-soft px-3.5 py-2.5 text-sm text-ink outline-none ring-1 ring-transparent placeholder:text-ink-faint focus:ring-teal"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <span v-if="error" class="text-xs text-danger">{{ error }}</span>
  </label>
</template>
