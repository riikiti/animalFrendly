<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

/** Числовая пагинация из секции «Пагинация и подгрузка» борда E — десктопные списки. */
const props = withDefaults(
  defineProps<{ page: number; pages: number; /** Сколько номеров показывать вокруг текущего. */ around?: number }>(),
  { around: 1 },
)

const emit = defineEmits<{ 'update:page': [page: number] }>()

// Собираем «1 … 4 5 6 … 12»: края всегда, вокруг текущего — по around штук.
const items = computed<(number | '…')[]>(() => {
  const shown = new Set<number>([1, props.pages])
  for (let i = props.page - props.around; i <= props.page + props.around; i++) {
    if (i >= 1 && i <= props.pages) shown.add(i)
  }

  const sorted = [...shown].sort((a, b) => a - b)
  const result: (number | '…')[] = []
  sorted.forEach((value, index) => {
    if (index > 0 && value - sorted[index - 1] > 1) result.push('…')
    result.push(value)
  })
  return result
})

const go = (page: number) => {
  if (page >= 1 && page <= props.pages && page !== props.page) emit('update:page', page)
}
</script>

<template>
  <nav class="flex items-center gap-1.5" aria-label="Страницы">
    <button
      type="button"
      class="grid size-10 place-items-center rounded-xl border-[1.5px] border-hairline bg-surface text-ink-soft transition-colors hover:bg-surface-soft disabled:cursor-not-allowed disabled:border-transparent disabled:bg-surface-soft disabled:text-ink-faint"
      :disabled="page <= 1"
      aria-label="Предыдущая страница"
      @click="go(page - 1)"
    >
      <ChevronLeft class="size-[17px]" />
    </button>

    <template v-for="(item, index) in items" :key="index">
      <span
        v-if="item === '…'"
        class="grid size-10 place-items-center text-sm text-ink-faint"
        aria-hidden="true"
        >…</span
      >
      <button
        v-else
        type="button"
        class="grid size-10 place-items-center rounded-xl border-[1.5px] text-sm transition-colors"
        :class="
          item === page
            ? 'border-transparent bg-accent font-bold text-accent-ink'
            : 'border-hairline bg-surface text-ink hover:bg-surface-soft'
        "
        :aria-current="item === page ? 'page' : undefined"
        @click="go(item)"
      >
        {{ item }}
      </button>
    </template>

    <button
      type="button"
      class="grid size-10 place-items-center rounded-xl border-[1.5px] border-hairline bg-surface text-ink-soft transition-colors hover:bg-surface-soft disabled:cursor-not-allowed disabled:border-transparent disabled:bg-surface-soft disabled:text-ink-faint"
      :disabled="page >= pages"
      aria-label="Следующая страница"
      @click="go(page + 1)"
    >
      <ChevronRight class="size-[17px]" />
    </button>
  </nav>
</template>
