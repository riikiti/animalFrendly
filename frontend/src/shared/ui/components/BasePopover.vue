<script setup lang="ts">
import { ref } from 'vue'
import { onClickOutside } from '@/shared/lib/onClickOutside'

/**
 * Поповер из секции «Тултипы и поповеры» борда D — пояснение по клику, например
 * «что значит проверенный профиль». В отличие от тултипа не исчезает при уводе мыши.
 */
withDefaults(defineProps<{ title?: string; align?: 'left' | 'right' }>(), { align: 'left' })

const open = ref(false)
const root = ref<HTMLElement | null>(null)

onClickOutside(root, open, () => (open.value = false))
</script>

<template>
  <span ref="root" class="relative inline-flex" @keydown.esc="open = false">
    <span :aria-expanded="open" @click="open = !open"><slot name="trigger" /></span>

    <Transition name="pop">
      <span
        v-if="open"
        class="absolute top-full z-40 mt-2 flex w-72 flex-col gap-2.5 rounded-[18px] border border-hairline bg-surface p-4 shadow-md"
        :class="align === 'right' ? 'right-0' : 'left-0'"
        role="dialog"
      >
        <span v-if="title" class="font-display text-sm font-bold text-ink">{{ title }}</span>
        <span class="text-[12.5px] leading-relaxed text-ink-soft"><slot /></span>
        <slot name="footer" />
      </span>
    </Transition>
  </span>
</template>

<style scoped>
.pop-enter-active,
.pop-leave-active {
  transition:
    opacity 0.16s ease,
    transform 0.16s ease;
}

.pop-enter-from,
.pop-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
