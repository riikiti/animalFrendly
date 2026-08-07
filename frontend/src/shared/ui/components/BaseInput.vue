<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, Eye, EyeOff } from 'lucide-vue-next'
import BaseField from './BaseField.vue'

/**
 * Однострочное поле по секции «Типы полей» борда B. Пропсы прежние — modelValue, label,
 * type, placeholder, error — плюс приставка, окончание, подсказка и состояние успеха.
 */
const props = withDefaults(
  defineProps<{
    modelValue: string
    label?: string
    type?: string
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
    /** Неизменяемая приставка слева, например «+7» у телефона. */
    prefix?: string
    /** Единица справа, например «₽» у цены. */
    suffix?: string
    /** Галочка в конце поля — значение проверено. */
    success?: boolean
    inputmode?: 'text' | 'numeric' | 'decimal' | 'tel' | 'email' | 'search'
  }>(),
  { type: 'text', disabled: false, success: false },
)

defineEmits<{ 'update:modelValue': [value: string] }>()

const revealed = ref(false)
const isPassword = computed(() => props.type === 'password')
const resolvedType = computed(() =>
  isPassword.value && revealed.value ? 'text' : (props.type ?? 'text'),
)
</script>

<template>
  <BaseField :label="label" :error="error" :hint="hint" :disabled="disabled">
    <template v-if="$slots.lead || prefix" #lead>
      <slot name="lead" />
      <span v-if="prefix" class="text-[15px] font-semibold text-ink">{{ prefix }}</span>
    </template>

    <input
      :type="resolvedType"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :inputmode="inputmode"
      class="w-full min-w-0 bg-transparent text-[15px] font-medium text-ink outline-none placeholder:text-ink-faint disabled:cursor-not-allowed disabled:text-ink-faint"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />

    <template v-if="isPassword || suffix || success || $slots.trail" #trail>
      <slot name="trail" />
      <span v-if="suffix" class="text-sm font-semibold text-ink-faint">{{ suffix }}</span>
      <Check v-if="success" class="size-[18px] shrink-0 text-teal" aria-hidden="true" />
      <button
        v-if="isPassword"
        type="button"
        class="shrink-0 text-ink-faint transition-colors hover:text-ink-soft"
        :aria-label="revealed ? 'Скрыть пароль' : 'Показать пароль'"
        @click="revealed = !revealed"
      >
        <EyeOff v-if="revealed" class="size-[18px]" />
        <Eye v-else class="size-[18px]" />
      </button>
    </template>
  </BaseField>
</template>
