<script setup lang="ts">
import { X } from 'lucide-vue-next'

/**
 * Чип из секций «Чипы · состояния» и «Фильтры» борда C. Размер sm — метка внутри
 * карточки, md — кнопка в ленте фильтров.
 */
withDefaults(
  defineProps<{
    /** glass — полупрозрачный чип поверх фотографии, ink — тёмная плашка. */
    tone?: 'neutral' | 'soft' | 'accent' | 'outline' | 'ink' | 'glass'
    size?: 'sm' | 'md'
    disabled?: boolean
    /** Кликабельный чип рисуется кнопкой, обычная метка — текстом. */
    interactive?: boolean
    /** Число в хвосте, например количество выбранных фильтров. */
    count?: number | string
    /** Крестик, снимающий фильтр. */
    removable?: boolean
  }>(),
  { tone: 'neutral', size: 'sm', disabled: false, interactive: false, removable: false },
)

defineEmits<{ remove: []; click: [event: MouseEvent] }>()

const toneClass = {
  neutral: 'bg-surface-soft text-ink-soft',
  soft: 'bg-accent-soft text-accent-text',
  accent: 'bg-accent text-accent-ink',
  outline: 'border-[1.5px] border-hairline bg-surface text-ink-soft',
  ink: 'bg-bezel text-bg',
  glass: 'border-[1.5px] border-white/30 bg-white/20 text-white backdrop-blur-sm',
}

const countClass = {
  neutral: 'bg-ink-faint/25 text-ink-soft',
  soft: 'bg-accent/20 text-accent-text',
  accent: 'bg-white/20 text-accent-ink',
  outline: 'bg-surface-soft text-ink-soft',
  ink: 'bg-accent text-accent-ink',
  glass: 'bg-white/25 text-white',
}
</script>

<template>
  <component
    :is="interactive ? 'button' : 'span'"
    :type="interactive ? 'button' : undefined"
    :disabled="interactive ? disabled : undefined"
    class="inline-flex shrink-0 items-center rounded-full transition-colors"
    :class="[
      size === 'md' ? 'h-[38px] gap-1.5 px-3.5 text-[13px] font-semibold' : 'gap-1.5 px-3 py-1.5 text-[13px] font-medium',
      toneClass[tone],
      disabled && 'cursor-not-allowed text-ink-faint',
      interactive && !disabled && 'hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
    ]"
    @click="$emit('click', $event)"
  >
    <slot name="icon" />
    <slot />
    <span
      v-if="count !== undefined"
      class="rounded-full px-1.5 text-[11px] font-bold"
      :class="countClass[tone]"
      >{{ count }}</span
    >
    <button
      v-if="removable"
      type="button"
      class="-mr-1 shrink-0 rounded-full p-0.5 transition-opacity hover:opacity-70"
      aria-label="Снять фильтр"
      @click.stop="$emit('remove')"
    >
      <X class="size-3.5" stroke-width="2.5" />
    </button>
  </component>
</template>
