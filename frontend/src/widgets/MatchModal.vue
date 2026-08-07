<script setup lang="ts">
import { Heart } from 'lucide-vue-next'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseOverlay from '@/shared/ui/components/BaseOverlay.vue'

defineProps<{ open: boolean }>()

const emit = defineEmits<{ continue: []; chat: [] }>()
</script>

<template>
  <BaseOverlay :open="open" persistent>
    <div
      class="flex flex-col items-center gap-4 rounded-3xl p-8 text-center text-white shadow-lg"
      style="background: linear-gradient(165deg, var(--accent-bright), var(--accent-pressed))"
    >
      <span class="grid size-16 place-items-center rounded-full bg-white/20">
        <Heart class="size-8 fill-current" aria-hidden="true" />
      </span>
      <span class="font-display text-3xl font-bold">Это мэтч!</span>
      <p class="text-sm opacity-85">Начните переписку, пока не остыло.</p>
      <BaseButton size="lg" block class="bg-white text-accent-text hover:bg-white" @click="emit('chat')">
        Написать сообщение
      </BaseButton>
      <button class="text-sm font-semibold text-white" @click="emit('continue')">
        Смотреть дальше
      </button>
    </div>
  </BaseOverlay>
</template>
