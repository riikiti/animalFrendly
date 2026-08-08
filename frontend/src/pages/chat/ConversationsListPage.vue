<script setup lang="ts">
import { useRouter } from 'vue-router'
import { MessagesSquare, X } from 'lucide-vue-next'
import ConversationsList from '@/widgets/ConversationsList.vue'

const router = useRouter()
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-none lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <h1 class="font-display text-xl font-bold text-ink">Чаты</h1>
      <button
        class="grid size-9 place-items-center rounded-full text-ink-faint transition-colors hover:bg-surface-soft"
        aria-label="Закрыть"
        @click="router.push({ name: 'home' })"
      >
        <X class="size-5" />
      </button>
    </div>

    <!-- На десктопе список — левая колонка, справа место под переписку; на телефоне
    беседа открывается отдельным экраном, поэтому правая колонка просто не показывается. -->
    <div class="flex-1 px-2 pb-4 lg:grid lg:grid-cols-[360px_1fr] lg:items-start lg:gap-4">
      <ConversationsList />

      <div
        class="hidden h-[70vh] place-items-center rounded-card border border-hairline bg-surface lg:grid"
      >
        <div class="flex flex-col items-center gap-2 text-center">
          <MessagesSquare class="size-8 text-ink-faint" aria-hidden="true" />
          <p class="text-sm text-ink-soft">Выберите беседу слева</p>
        </div>
      </div>
    </div>
  </div>
</template>
