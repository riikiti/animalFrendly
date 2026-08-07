<script setup lang="ts">
import { computed } from 'vue'

/**
 * Счётчик-таблетка из секции «Бейджи статусов» борда C: непрочитанные сообщения,
 * новые лайки, метка NEW.
 */
const props = withDefaults(
  defineProps<{
    value: number | string
    tone?: 'accent' | 'danger' | 'ink'
    /** Больше этого числа показываем «99+». */
    limit?: number
  }>(),
  { tone: 'accent', limit: 99 },
)

const toneClass = {
  accent: 'bg-accent text-accent-ink',
  danger: 'bg-danger-fill text-white',
  ink: 'bg-bezel text-bg',
}

const shown = computed(() =>
  typeof props.value === 'number' && props.value > props.limit
    ? `${props.limit}+`
    : String(props.value),
)
</script>

<template>
  <span
    class="inline-flex h-[22px] min-w-[22px] shrink-0 items-center justify-center rounded-full px-2 text-[11px] font-bold"
    :class="toneClass[tone]"
    >{{ shown }}</span
  >
</template>
