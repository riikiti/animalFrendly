<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import BaseSpinner from './BaseSpinner.vue'

/**
 * Подгрузка списка из секции «Пагинация и подгрузка» борда E: кнопка «Показать ещё»,
 * которая на время загрузки превращается в строку со спиннером.
 */
withDefaults(
  defineProps<{
    /** Сколько ещё элементов подгрузится — приписывается к подписи. */
    count?: number
    loading?: boolean
    /** Текст в состоянии загрузки. */
    loadingLabel?: string
  }>(),
  { loading: false, loadingLabel: 'Загружаем ещё…' },
)

defineEmits<{ load: [] }>()
</script>

<template>
  <div
    v-if="loading"
    class="flex items-center justify-center gap-2.5 rounded-2xl border border-hairline bg-surface p-4"
  >
    <BaseSpinner :label="loadingLabel" />
  </div>

  <button
    v-else
    type="button"
    class="inline-flex h-11 items-center gap-2 rounded-full border-[1.5px] border-hairline bg-surface px-5 text-sm font-semibold text-ink transition-colors hover:bg-surface-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    @click="$emit('load')"
  >
    <ChevronDown class="size-4 text-ink-soft" aria-hidden="true" />
    {{ count ? `Показать ещё ${count}` : 'Показать ещё' }}
  </button>
</template>
