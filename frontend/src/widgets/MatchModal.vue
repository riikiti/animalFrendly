<script setup lang="ts">
defineProps<{ open: boolean }>()
const emit = defineEmits<{ continue: []; chat: [] }>()
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6">
    <div
      class="flex w-full max-w-sm flex-col items-center gap-4 rounded-3xl p-8 text-center text-white"
      style="background: linear-gradient(165deg, var(--teal), #16544a)"
    >
      <span class="font-display text-3xl">Это мэтч!</span>
      <p class="text-sm opacity-85">Начните переписку, пока не остыло.</p>
      <button
        class="rounded-full bg-accent px-5 py-2.5 text-sm font-semibold text-accent-ink"
        @click="emit('chat')"
      >
        Написать сообщение
      </button>
      <button class="text-sm font-semibold text-white" @click="emit('continue')">
        Смотреть дальше
      </button>
    </div>
  </div>
</template>
