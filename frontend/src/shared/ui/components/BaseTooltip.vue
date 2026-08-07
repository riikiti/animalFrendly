<script setup lang="ts">
import { ref, useId } from 'vue'

/**
 * Тултип из секции «Шторки» борда D. Показывается по наведению и по фокусу с клавиатуры,
 * поэтому годится и для иконочных кнопок.
 */
withDefaults(defineProps<{ text: string; position?: 'top' | 'bottom' }>(), { position: 'top' })

const shown = ref(false)
const tooltipId = useId()
</script>

<template>
  <span
    class="relative inline-flex"
    @mouseenter="shown = true"
    @mouseleave="shown = false"
    @focusin="shown = true"
    @focusout="shown = false"
  >
    <span :aria-describedby="tooltipId"><slot /></span>

    <Transition name="tip">
      <span
        v-if="shown"
        :id="tooltipId"
        role="tooltip"
        class="pointer-events-none absolute left-1/2 z-40 w-max max-w-56 -translate-x-1/2 rounded-xl bg-bezel px-3 py-2 text-[12.5px] text-white shadow-md"
        :class="position === 'top' ? 'bottom-full mb-2' : 'top-full mt-2'"
        >{{ text }}</span
      >
    </Transition>
  </span>
</template>

<style scoped>
.tip-enter-active,
.tip-leave-active {
  transition: opacity 0.14s ease;
}

.tip-enter-from,
.tip-leave-to {
  opacity: 0;
}
</style>
