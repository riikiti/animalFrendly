<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'

/**
 * Поле кода из СМС по секции «Типы полей» борда B: отдельная клетка на каждую цифру,
 * автопереход вперёд и назад, вставка кода целиком из буфера.
 */
const props = withDefaults(
  defineProps<{
    modelValue: string
    length?: number
    label?: string
    error?: string
    disabled?: boolean
    /** Автофокус на первой клетке — экран открывается сразу после отправки кода. */
    autofocus?: boolean
  }>(),
  { length: 4, disabled: false, autofocus: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: string]; complete: [value: string] }>()

const boxes = ref<HTMLInputElement[]>([])

const digits = computed(() =>
  Array.from({ length: props.length }, (_, index) => props.modelValue[index] ?? ''),
)

const push = (value: string) => {
  const clean = value.replace(/\D/g, '').slice(0, props.length)
  emit('update:modelValue', clean)
  if (clean.length === props.length) emit('complete', clean)
  return clean
}

const focusBox = async (index: number) => {
  await nextTick()
  boxes.value[Math.min(Math.max(index, 0), props.length - 1)]?.focus()
}

const onInput = (event: Event, index: number) => {
  const input = event.target as HTMLInputElement
  const typed = input.value.replace(/\D/g, '')
  // Значение хранится строкой целиком, поэтому клетку перерисовываем из неё.
  input.value = digits.value[index]

  if (!typed) return
  const next = props.modelValue.slice(0, index) + typed + props.modelValue.slice(index + typed.length)
  const clean = push(next)
  focusBox(Math.min(index + typed.length, clean.length))
}

const onKeydown = (event: KeyboardEvent, index: number) => {
  if (event.key === 'Backspace') {
    event.preventDefault()
    const next = props.modelValue.slice(0, Math.max(index - (digits.value[index] ? 0 : 1), 0))
    push(next)
    focusBox(digits.value[index] ? index : index - 1)
  }
  if (event.key === 'ArrowLeft') focusBox(index - 1)
  if (event.key === 'ArrowRight') focusBox(index + 1)
}

const onPaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const clean = push(event.clipboardData?.getData('text') ?? '')
  focusBox(clean.length)
}
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <span v-if="label" class="text-xs font-semibold text-ink-soft">{{ label }}</span>

    <div class="flex gap-2.5">
      <input
        v-for="(digit, index) in digits"
        :key="index"
        ref="boxes"
        :value="digit"
        type="text"
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength="1"
        :disabled="disabled"
        :autofocus="autofocus && index === 0"
        :aria-label="`Цифра ${index + 1} из ${length}`"
        class="h-15 w-full min-w-0 rounded-[14px] border-[1.5px] text-center font-display text-xl font-bold text-ink outline-none transition-colors focus:border-accent focus:bg-surface-soft disabled:cursor-not-allowed disabled:bg-surface-soft disabled:text-ink-faint"
        :class="error ? 'border-danger bg-surface' : 'border-hairline bg-surface'"
        @input="onInput($event, index)"
        @keydown="onKeydown($event, index)"
        @paste="onPaste"
      />
    </div>

    <span v-if="error" class="text-xs text-danger">{{ error }}</span>
  </div>
</template>
