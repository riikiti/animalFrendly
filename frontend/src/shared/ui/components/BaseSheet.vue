<script setup lang="ts">
import { useId } from 'vue'
import { X } from 'lucide-vue-next'
import BaseOverlay from './BaseOverlay.vue'

/**
 * Шторка снизу из секции «Шторки (bottom sheets)» борда D: ручка, заголовок,
 * содержимое и прижатые к низу действия.
 */
withDefaults(
  defineProps<{
    open: boolean
    title?: string
    /** Крестик справа от заголовка. */
    closable?: boolean
    persistent?: boolean
  }>(),
  { closable: false, persistent: false },
)

const emit = defineEmits<{ close: [] }>()

const titleId = useId()
</script>

<template>
  <BaseOverlay
    :open="open"
    position="bottom"
    :persistent="persistent"
    :labelled-by="title ? titleId : undefined"
    @close="emit('close')"
  >
    <div class="flex max-h-[85vh] flex-col gap-3.5 rounded-t-[26px] bg-surface px-5 pt-2.5 pb-6">
      <span class="mx-auto h-1 w-11 shrink-0 rounded-full bg-hairline" aria-hidden="true" />

      <div v-if="title" class="flex shrink-0 items-center justify-between gap-3">
        <h2 :id="titleId" class="font-display text-lg font-bold text-ink">{{ title }}</h2>
        <button
          v-if="closable"
          type="button"
          class="rounded-full p-1 text-ink-faint transition-colors hover:text-ink"
          aria-label="Закрыть"
          @click="emit('close')"
        >
          <X class="size-5" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto"><slot /></div>

      <div v-if="$slots.actions" class="flex shrink-0 flex-col gap-2.5">
        <slot name="actions" />
      </div>
    </div>
  </BaseOverlay>
</template>
