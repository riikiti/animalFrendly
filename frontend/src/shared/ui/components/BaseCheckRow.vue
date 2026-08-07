<script setup lang="ts">
import { Check } from 'lucide-vue-next'

/**
 * Строка с галочкой из компонента Check Row макета маркетплейса: пункты подтверждения
 * получения товара, состав тарифа, варианты доставки.
 */
withDefaults(
  defineProps<{
    checked?: boolean
    /** Кликабельная строка — пункт, который пользователь отмечает сам. */
    interactive?: boolean
    disabled?: boolean
  }>(),
  { checked: true, interactive: false, disabled: false },
)

defineEmits<{ 'update:checked': [value: boolean] }>()
</script>

<template>
  <component
    :is="interactive ? 'button' : 'div'"
    :type="interactive ? 'button' : undefined"
    :disabled="interactive ? disabled : undefined"
    class="flex w-full items-center gap-2.5 text-left"
    :class="interactive && 'transition-opacity hover:opacity-80 disabled:cursor-not-allowed'"
    @click="interactive && $emit('update:checked', !checked)"
  >
    <span
      class="grid size-[22px] shrink-0 place-items-center rounded-[7px] transition-colors"
      :class="checked ? 'bg-accent text-accent-ink' : 'border-[1.8px] border-hairline bg-surface'"
      aria-hidden="true"
    >
      <Check v-if="checked" class="size-[13px]" stroke-width="3" />
    </span>
    <span class="text-[13.5px] leading-snug text-ink"><slot /></span>
  </component>
</template>
