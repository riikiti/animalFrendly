<script setup lang="ts">
import { Check } from 'lucide-vue-next'

/**
 * Шаг ленты статусов из компонента Status Step макета маркетплейса — история заказа
 * и ход рассмотрения спора.
 */
withDefaults(
  defineProps<{
    title: string
    /** Дата и время шага. */
    meta?: string
    /** done — пройден, current — идёт сейчас, upcoming — впереди. */
    state?: 'done' | 'current' | 'upcoming'
    /** Последний шаг не рисует линию вниз. */
    last?: boolean
  }>(),
  { state: 'upcoming', last: false },
)

const dotClass = {
  done: 'bg-teal text-white',
  current: 'border-[2.5px] border-accent bg-surface',
  upcoming: 'border-[2.5px] border-hairline bg-surface',
}
</script>

<template>
  <div class="flex gap-3">
    <div class="flex w-7 shrink-0 flex-col items-center">
      <span class="grid size-7 place-items-center rounded-full" :class="dotClass[state]">
        <Check v-if="state === 'done'" class="size-3.5" stroke-width="3" aria-hidden="true" />
        <span v-else-if="state === 'current'" class="size-2.5 rounded-full bg-accent" />
      </span>
      <span
        v-if="!last"
        class="w-[2.5px] flex-1 rounded-full"
        :class="state === 'done' ? 'bg-teal' : 'bg-hairline'"
      />
    </div>

    <div class="flex flex-1 flex-col gap-1 pt-1 pb-5">
      <p class="text-sm font-bold" :class="state === 'upcoming' ? 'text-ink-faint' : 'text-ink'">
        {{ title }}
      </p>
      <p v-if="meta" class="text-xs text-ink-faint">{{ meta }}</p>
      <slot />
    </div>
  </div>
</template>
