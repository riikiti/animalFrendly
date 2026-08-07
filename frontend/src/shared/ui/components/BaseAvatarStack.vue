<script setup lang="ts">
import { computed } from 'vue'
import BaseAvatar from './BaseAvatar.vue'

/** Стопка аватаров из секции «Аватары» борда C — участники чата, лайкнувшие анкету. */
const props = withDefaults(
  defineProps<{
    people: { src?: string | null; name?: string }[]
    /** Сколько показать до счётчика «+N». */
    max?: number
    size?: 'xs' | 'sm' | 'md'
  }>(),
  { max: 3, size: 'sm' },
)

const shown = computed(() => props.people.slice(0, props.max))
const rest = computed(() => props.people.length - shown.value.length)

const restClass = { xs: 'size-7 text-[10px]', sm: 'size-9 text-xs', md: 'size-11 text-sm' }
</script>

<template>
  <span class="flex items-center -space-x-2.5">
    <BaseAvatar
      v-for="(person, index) in shown"
      :key="index"
      :src="person.src"
      :name="person.name"
      :size="size"
    />
    <span
      v-if="rest > 0"
      class="grid shrink-0 place-items-center rounded-full bg-bezel font-display font-bold text-bg ring-2 ring-surface"
      :class="restClass[size]"
      >+{{ rest }}</span
    >
  </span>
</template>
