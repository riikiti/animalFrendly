<script setup lang="ts">
defineProps<{ modelValue: boolean }>()
defineEmits<{ 'update:modelValue': [value: boolean] }>()
</script>

<template>
  <label class="flex items-start gap-2 text-xs leading-relaxed text-ink-faint">
    <input
      type="checkbox"
      :checked="modelValue"
      class="mt-0.5 h-4 w-4 shrink-0 accent-teal"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span><slot /></span>
  </label>
</template>
