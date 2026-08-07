<script setup lang="ts">
import { Crown } from 'lucide-vue-next'
import BaseButton from './BaseButton.vue'
import BaseSheet from './BaseSheet.vue'

withDefaults(defineProps<{ open: boolean; message?: string }>(), {
  message: 'Лимит по бесплатному тарифу исчерпан.',
})

const emit = defineEmits<{ close: []; upgrade: [] }>()
</script>

<template>
  <BaseSheet :open="open" title="Нужна подписка" closable @close="emit('close')">
    <div class="flex flex-col items-center gap-3 pb-2 text-center">
      <span class="grid size-14 place-items-center rounded-full bg-accent-soft text-accent-text">
        <Crown class="size-6" aria-hidden="true" />
      </span>
      <p class="text-sm text-ink-soft">{{ message }}</p>
    </div>

    <template #actions>
      <BaseButton size="lg" block @click="emit('upgrade')">Оформить подписку</BaseButton>
      <BaseButton variant="ghost" block @click="emit('close')">Не сейчас</BaseButton>
    </template>
  </BaseSheet>
</template>
