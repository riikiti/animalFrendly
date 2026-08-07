<script setup lang="ts">
/** Спиннер из секции «Состояния загрузки» борда E: три размера, коралловый или серый. */
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg'; tone?: 'accent' | 'ink'; label?: string }>(), {
  size: 'md',
  tone: 'accent',
})

const sizeClass = { sm: 'size-[18px]', md: 'size-[26px]', lg: 'size-[38px]' }
const widths = { sm: 2.25, md: 3.25, lg: 4.75 }
</script>

<template>
  <span class="inline-flex items-center gap-2.5">
    <svg
      class="animate-spin"
      :class="[sizeClass[size], tone === 'accent' ? 'text-accent' : 'text-ink-faint']"
      viewBox="0 0 24 24"
      fill="none"
      role="status"
      :aria-label="label ?? 'Загрузка'"
    >
      <circle cx="12" cy="12" r="9" stroke="currentColor" :stroke-width="widths[size]" opacity="0.2" />
      <path
        d="M21 12a9 9 0 0 0-9-9"
        stroke="currentColor"
        :stroke-width="widths[size]"
        stroke-linecap="round"
      />
    </svg>
    <span v-if="label" class="text-[13px] text-ink-soft">{{ label }}</span>
  </span>
</template>
