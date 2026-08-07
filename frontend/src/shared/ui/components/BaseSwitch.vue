<script setup lang="ts">
/** Тумблер из секции «Переключатели и выбор» борда B: дорожка 52×30, шарик 24. */
withDefaults(
  defineProps<{
    modelValue: boolean
    /** Подпись слева от тумблера — так собраны строки настроек в макете. */
    label?: string
    description?: string
    disabled?: boolean
  }>(),
  { disabled: false },
)

defineEmits<{ 'update:modelValue': [value: boolean] }>()
</script>

<template>
  <label
    class="flex items-center gap-3"
    :class="[label ? 'w-full justify-between' : 'inline-flex', disabled && 'cursor-not-allowed']"
  >
    <span v-if="label" class="flex flex-col gap-0.5">
      <span class="text-sm font-semibold" :class="disabled ? 'text-ink-faint' : 'text-ink'">{{
        label
      }}</span>
      <span v-if="description" class="text-xs text-ink-faint">{{ description }}</span>
    </span>

    <input
      type="checkbox"
      role="switch"
      :checked="modelValue"
      :disabled="disabled"
      class="peer sr-only"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span
      class="flex h-[30px] w-[52px] shrink-0 items-center rounded-full p-[3px] transition-colors peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent"
      :class="disabled ? 'bg-surface-soft' : modelValue ? 'bg-accent' : 'bg-hairline'"
      aria-hidden="true"
    >
      <span
        class="size-6 rounded-full bg-surface shadow-sm transition-transform"
        :class="modelValue && 'translate-x-[22px]'"
      />
    </span>
  </label>
</template>
