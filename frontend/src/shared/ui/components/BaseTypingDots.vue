<script setup lang="ts">
/** «Печатает…» из секции «Спиннеры и точки» борда E — индикатор набора в чате. */
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full bg-surface-soft px-3.5 py-2.5"
    role="status"
    aria-label="Собеседник печатает"
  >
    <span
      v-for="dot in 3"
      :key="dot"
      class="size-2 rounded-full bg-ink-faint"
      :style="{ animation: `typing 1.2s ${(dot - 1) * 0.16}s ease-in-out infinite` }"
    />
  </span>
</template>

<style>
@keyframes typing {
  0%,
  60%,
  100% {
    opacity: 0.35;
    transform: translateY(0);
  }
  30% {
    opacity: 1;
    transform: translateY(-3px);
  }
}
</style>
