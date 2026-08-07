<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { Check, ChevronDown } from 'lucide-vue-next'
import { onClickOutside } from '@/shared/lib/onClickOutside'

/**
 * Рисованный выпадающий список из секции «Типы полей» борда B — со своими иконками
 * и галочкой выбранного пункта. В отличие от BaseSelect на нативном контроле,
 * здесь список выглядит одинаково во всех браузерах, но клавиатуру пришлось описать
 * руками: стрелки, Home/End, Enter, Esc.
 */
const props = withDefaults(
  defineProps<{
    modelValue: string | number | null
    options: { value: string | number; label: string; description?: string }[]
    label?: string
    placeholder?: string
    error?: string
    hint?: string
    disabled?: boolean
  }>(),
  { placeholder: 'Не выбрано', disabled: false },
)

const emit = defineEmits<{ 'update:modelValue': [value: string | number] }>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const items = ref<HTMLElement[]>([])
const activeIndex = ref(-1)

onClickOutside(root, open, () => (open.value = false))

const selected = computed(() => props.options.find((option) => option.value === props.modelValue))

const focusItem = async (index: number) => {
  const count = props.options.length
  if (!count) return
  activeIndex.value = (index + count) % count
  await nextTick()
  items.value[activeIndex.value]?.focus()
}

const toggle = async () => {
  if (props.disabled) return
  open.value = !open.value
  if (open.value) {
    const start = props.options.findIndex((option) => option.value === props.modelValue)
    await focusItem(start === -1 ? 0 : start)
  }
}

const choose = (value: string | number) => {
  emit('update:modelValue', value)
  open.value = false
}
</script>

<template>
  <div ref="root" class="relative flex flex-col gap-1.5">
    <span v-if="label" class="text-xs font-semibold text-ink-soft">{{ label }}</span>

    <button
      type="button"
      role="combobox"
      :aria-expanded="open"
      aria-haspopup="listbox"
      :disabled="disabled"
      class="flex h-13 items-center gap-2.5 rounded-[14px] border-[1.5px] bg-surface px-4 text-left transition-colors disabled:cursor-not-allowed disabled:border-transparent disabled:bg-surface-soft"
      :class="[
        open ? 'border-accent' : error ? 'border-danger' : 'border-hairline',
      ]"
      @click="toggle"
      @keydown.down.prevent="open ? focusItem(activeIndex + 1) : toggle()"
      @keydown.esc="open = false"
    >
      <slot name="lead" />
      <span
        class="min-w-0 flex-1 truncate text-[15px] font-medium"
        :class="selected ? 'text-ink' : 'text-ink-faint'"
        >{{ selected?.label ?? placeholder }}</span
      >
      <ChevronDown
        class="size-[18px] shrink-0 text-ink-faint transition-transform"
        :class="open && 'rotate-180'"
        aria-hidden="true"
      />
    </button>

    <Transition name="menu">
      <ul
        v-if="open"
        class="absolute top-full right-0 left-0 z-40 mt-1.5 max-h-64 overflow-y-auto rounded-[14px] border border-hairline bg-surface p-1.5 shadow-md"
        :class="label && 'top-[calc(100%+0px)]'"
        role="listbox"
      >
        <li v-for="(option, index) in options" :key="option.value">
          <button
            ref="items"
            type="button"
            role="option"
            :aria-selected="option.value === modelValue"
            class="flex w-full items-center gap-2.5 rounded-[10px] px-3 py-2.5 text-left transition-colors hover:bg-surface-soft focus:bg-surface-soft focus:outline-none"
            @click="choose(option.value)"
            @keydown.down.prevent="focusItem(index + 1)"
            @keydown.up.prevent="focusItem(index - 1)"
            @keydown.home.prevent="focusItem(0)"
            @keydown.end.prevent="focusItem(options.length - 1)"
            @keydown.esc="open = false"
          >
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-medium text-ink">{{ option.label }}</span>
              <span v-if="option.description" class="block truncate text-xs text-ink-faint">{{
                option.description
              }}</span>
            </span>
            <Check
              v-if="option.value === modelValue"
              class="size-4 shrink-0 text-accent-text"
              aria-hidden="true"
            />
          </button>
        </li>
      </ul>
    </Transition>

    <span v-if="error" class="text-xs text-danger">{{ error }}</span>
    <span v-else-if="hint" class="text-xs text-ink-faint">{{ hint }}</span>
  </div>
</template>

<style scoped>
.menu-enter-active,
.menu-leave-active {
  transition:
    opacity 0.14s ease,
    transform 0.14s ease;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
