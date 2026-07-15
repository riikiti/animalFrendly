<script setup lang="ts">
withDefaults(defineProps<{ open: boolean; message?: string }>(), {
  message: 'Лимит по бесплатному тарифу исчерпан.',
})
const emit = defineEmits<{ close: []; upgrade: [] }>()
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-end justify-center bg-black/40"
    @click.self="emit('close')"
  >
    <div class="flex w-full max-w-sm flex-col gap-4 rounded-t-3xl bg-surface p-6 pb-8">
      <div class="flex items-center justify-between">
        <span class="font-display text-lg text-ink">Нужна подписка</span>
        <button class="text-sm text-ink-faint" @click="emit('close')">✕</button>
      </div>

      <p class="text-sm text-ink-soft">{{ message }}</p>

      <button
        class="inline-flex items-center justify-center gap-1.5 rounded-full bg-accent px-5 py-2.5 text-sm font-semibold text-accent-ink transition hover:brightness-105"
        @click="emit('upgrade')"
      >
        Оформить подписку
      </button>
      <button class="text-sm font-semibold text-ink-faint" @click="emit('close')">Не сейчас</button>
    </div>
  </div>
</template>
