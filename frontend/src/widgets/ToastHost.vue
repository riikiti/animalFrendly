<script setup lang="ts">
import { dismissToast, useToasts } from '@/shared/lib/toast'
import BaseToast from '@/shared/ui/components/BaseToast.vue'

/** Место, где показываются тосты. Подключается один раз в App.vue. */
const toasts = useToasts()

const runAction = (id: number, action?: () => void) => {
  action?.()
  dismissToast(id)
}
</script>

<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed inset-x-0 bottom-0 z-60 flex flex-col items-center gap-2.5 p-4"
    >
      <TransitionGroup name="toast">
        <BaseToast
          v-for="toast in toasts"
          :key="toast.id"
          :toast="toast"
          class="max-w-sm"
          @dismiss="dismissToast(toast.id)"
          @action="runAction(toast.id, toast.onAction)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.22s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
