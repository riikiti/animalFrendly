<script setup lang="ts">
/** Точечный индикатор присутствия из секции «Бейджи статусов» борда C. */
withDefaults(defineProps<{ tone?: 'online' | 'recent' | 'offline' }>(), { tone: 'online' })

const toneClass = { online: 'bg-teal', recent: 'bg-gold', offline: 'bg-ink-faint' }
</script>

<template>
  <span class="inline-flex items-center gap-1.5 text-[13px] text-ink-soft">
    <span class="size-2 shrink-0 rounded-full" :class="toneClass[tone]" aria-hidden="true" />
    <slot />
  </span>
</template>
