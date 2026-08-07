<script setup lang="ts">
import { useId } from 'vue'
import { X } from 'lucide-vue-next'
import BaseOverlay from './BaseOverlay.vue'

/**
 * Модальное окно из секции «Модальные окна» борда D. По умолчанию мобильный вид —
 * иконка в кружке, заголовок, текст и кнопки; wide даёт десктопный с шапкой и подвалом.
 */
withDefaults(
  defineProps<{
    open: boolean
    title: string
    description?: string
    /** Цвет кружка с иконкой: обычное действие или опасное. */
    tone?: 'accent' | 'danger'
    /** Широкое окно с шапкой, прокруткой и подвалом — для форм на десктопе. */
    wide?: boolean
    persistent?: boolean
    /** Крестик в углу. У подтверждений его нет, у форм есть. */
    closable?: boolean
  }>(),
  { tone: 'accent', wide: false, persistent: false, closable: false },
)

const emit = defineEmits<{ close: [] }>()

const titleId = useId()
</script>

<template>
  <BaseOverlay
    :open="open"
    :persistent="persistent"
    :labelled-by="titleId"
    @close="emit('close')"
  >
    <div
      class="relative flex flex-col overflow-hidden rounded-3xl bg-surface shadow-lg"
      :class="wide ? 'max-h-[80vh]' : ''"
    >
      <button
        v-if="closable"
        type="button"
        class="absolute top-4 right-4 z-10 rounded-full p-1 text-ink-faint transition-colors hover:text-ink"
        aria-label="Закрыть"
        @click="emit('close')"
      >
        <X class="size-5" />
      </button>

      <template v-if="wide">
        <header class="border-b border-hairline px-6 py-5">
          <h2 :id="titleId" class="font-display text-lg font-bold text-ink">{{ title }}</h2>
          <p v-if="description" class="mt-1 text-sm text-ink-soft">{{ description }}</p>
        </header>
        <div class="flex-1 overflow-y-auto px-6 py-6"><slot /></div>
        <footer
          v-if="$slots.actions"
          class="flex justify-end gap-2.5 border-t border-hairline bg-bg px-6 py-4"
        >
          <slot name="actions" />
        </footer>
      </template>

      <template v-else>
        <div class="flex flex-col items-center gap-3.5 p-6 text-center">
          <span
            v-if="$slots.icon"
            class="grid size-14 place-items-center rounded-full"
            :class="tone === 'danger' ? 'bg-danger-soft text-danger' : 'bg-accent-soft text-accent-text'"
          >
            <slot name="icon" />
          </span>
          <h2 :id="titleId" class="font-display text-xl font-bold text-ink">{{ title }}</h2>
          <p v-if="description" class="text-[13.5px] leading-relaxed text-ink-soft">
            {{ description }}
          </p>
          <slot />
          <div v-if="$slots.actions" class="mt-1 flex w-full flex-col gap-2.5">
            <slot name="actions" />
          </div>
        </div>
      </template>
    </div>
  </BaseOverlay>
</template>
