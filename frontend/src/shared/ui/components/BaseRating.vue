<script setup lang="ts">
import { computed } from 'vue'
import { Star } from 'lucide-vue-next'

/** Рейтинг из секции «Аватары» борда C: пять звёзд либо компактная таблетка. */
const props = withDefaults(
  defineProps<{
    value: number
    /** Число отзывов рядом со звёздами. */
    count?: number
    /** Компактный вид — золотая таблетка со звездой, как у топ-продавца. */
    compact?: boolean
    /** Приписка в компактном виде, например «Топ продавец». */
    note?: string
  }>(),
  { compact: false },
)

// Округляем вниз: у 4,8 в макете горит четыре звезды, а не пять.
const filled = computed(() => Math.floor(props.value))
const formatted = computed(() => props.value.toFixed(1).replace('.', ','))
</script>

<template>
  <span
    v-if="compact"
    class="inline-flex items-center gap-1.5 rounded-full bg-gold-soft px-2.5 py-1.5 text-xs font-bold text-gold-text"
  >
    <Star class="size-3.5 fill-current" aria-hidden="true" />
    {{ formatted }}<template v-if="note"> · {{ note }}</template>
  </span>

  <span v-else class="inline-flex items-center gap-1.5" :aria-label="`Оценка ${formatted} из 5`">
    <span class="flex gap-0.5" aria-hidden="true">
      <Star
        v-for="star in 5"
        :key="star"
        class="size-[17px] fill-current"
        :class="star <= filled ? 'text-gold' : 'text-hairline'"
      />
    </span>
    <span class="text-[15px] font-bold text-ink">{{ formatted }}</span>
    <span v-if="count !== undefined" class="text-[13px] text-ink-faint">· {{ count }} отзывов</span>
  </span>
</template>
