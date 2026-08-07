<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'

/**
 * Общая механика затемнения для модалок и шторок борда D: перенос в конец body,
 * закрытие по Esc и по клику мимо, блокировка прокрутки под собой, возврат фокуса.
 */
const props = withDefaults(
  defineProps<{
    open: boolean
    /** center — модальное окно, bottom — шторка снизу. */
    position?: 'center' | 'bottom'
    /** Запретить закрытие по клику мимо и по Esc — для незавершаемых операций. */
    persistent?: boolean
    labelledBy?: string
  }>(),
  { position: 'center', persistent: false },
)

const emit = defineEmits<{ close: [] }>()

const panel = ref<HTMLElement | null>(null)
let restoreFocus: HTMLElement | null = null

const close = () => {
  if (!props.persistent) emit('close')
}

const onKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') close()
}

watch(
  () => props.open,
  async (open) => {
    if (open) {
      restoreFocus = document.activeElement as HTMLElement | null
      document.body.style.overflow = 'hidden'
      document.addEventListener('keydown', onKeydown)
      await Promise.resolve()
      panel.value?.focus()
    } else {
      document.body.style.overflow = ''
      document.removeEventListener('keydown', onKeydown)
      restoreFocus?.focus()
      restoreFocus = null
    }
  },
)

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex justify-center bg-bezel/65 p-5"
        :class="position === 'bottom' ? 'items-end p-0' : 'items-center'"
        @click.self="close"
      >
        <div
          ref="panel"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="labelledBy"
          tabindex="-1"
          class="w-full outline-none"
          :class="position === 'bottom' ? 'max-w-md' : 'max-w-md'"
        >
          <slot />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.18s ease;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}
</style>
