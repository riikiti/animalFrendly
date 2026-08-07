<script setup lang="ts">
import { computed } from 'vue'
import { Check, PawPrint } from 'lucide-vue-next'

/**
 * Аватар из секции «Аватары» борда C. Размеры те же: 28 / 36 / 44 / 56 / 72 / 96.
 * Без фотографии показывает инициалы, без имени — лапку.
 */
const props = withDefaults(
  defineProps<{
    src?: string | null
    /** Имя нужно и для инициалов, и для подписи картинки. */
    name?: string
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    /** Приюты и организации в макете нарисованы скруглённым квадратом. */
    shape?: 'circle' | 'rounded'
    presence?: 'online' | 'recent' | 'offline' | null
    /** Галочка подтверждённого профиля в углу. */
    verified?: boolean
  }>(),
  { size: 'md', shape: 'circle', presence: null, verified: false },
)

const sizeClass = {
  xs: 'size-7 text-[10px]',
  sm: 'size-9 text-xs',
  md: 'size-11 text-sm',
  lg: 'size-14 text-lg',
  xl: 'size-18 text-xl',
  '2xl': 'size-24 text-2xl',
}

const dotClass = {
  xs: 'size-2.5',
  sm: 'size-3',
  md: 'size-3.5',
  lg: 'size-3.5',
  xl: 'size-4',
  '2xl': 'size-5',
}

const presenceClass = {
  online: 'bg-teal',
  recent: 'bg-gold',
  offline: 'bg-ink-faint',
}

const initials = computed(() => {
  if (!props.name) return ''
  return props.name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0])
    .join('')
    .toUpperCase()
})
</script>

<template>
  <span class="relative inline-flex shrink-0" :class="sizeClass[size]">
    <span
      class="grid size-full place-items-center overflow-hidden bg-accent-soft font-display font-bold text-accent-text ring-2 ring-surface"
      :class="shape === 'circle' ? 'rounded-full' : 'rounded-xl'"
    >
      <img v-if="src" :src="src" :alt="name ?? ''" class="size-full object-cover" />
      <template v-else-if="initials">{{ initials }}</template>
      <PawPrint v-else class="size-1/2 opacity-70" aria-hidden="true" />
    </span>

    <span
      v-if="presence"
      class="absolute right-0 bottom-0 rounded-full ring-[2.5px] ring-surface"
      :class="[dotClass[size], presenceClass[presence]]"
      :aria-label="{ online: 'В сети', recent: 'Недавно', offline: 'Не в сети' }[presence]"
    />

    <span
      v-else-if="verified"
      class="absolute -right-1 -bottom-1 grid place-items-center rounded-full bg-teal ring-[2.5px] ring-surface"
      :class="dotClass[size] === 'size-2.5' ? 'size-4' : 'size-6'"
      aria-label="Профиль подтверждён"
    >
      <Check class="size-3 text-white" stroke-width="3" />
    </span>
  </span>
</template>
