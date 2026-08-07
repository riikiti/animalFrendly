<script setup lang="ts">
/** Сегментированный контрол из секции «Переключатели и выбор» борда B. */
withDefaults(
  defineProps<{
    modelValue: string | number
    options: { value: string | number; label: string }[]
    /** Название группы для читалок, например «Вид животного». */
    ariaLabel?: string
    disabled?: boolean
  }>(),
  { disabled: false },
)

defineEmits<{ 'update:modelValue': [value: string | number] }>()
</script>

<template>
  <div
    class="flex w-full gap-0.5 rounded-full bg-surface-soft p-1"
    role="radiogroup"
    :aria-label="ariaLabel"
  >
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.value"
      :disabled="disabled"
      class="h-9 flex-1 rounded-full px-3 text-[13px] font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:text-ink-faint"
      :class="
        modelValue === option.value
          ? 'bg-surface text-ink shadow-sm'
          : 'text-ink-soft hover:text-ink'
      "
      @click="$emit('update:modelValue', option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>
