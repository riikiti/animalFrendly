<script setup lang="ts">
/**
 * Шаги регистрации из секции «Пагинация и подгрузка» борда E — полоски по числу шагов
 * и подпись «Шаг 2 из 4».
 */
withDefaults(
  defineProps<{
    steps: number
    /** Номер текущего шага, начиная с 1. */
    current: number
    /** Название шага, приписывается к «Шаг N из M». */
    stepLabel?: string
  }>(),
  {},
)
</script>

<template>
  <div class="flex flex-col gap-2.5">
    <div class="flex gap-1.5" role="progressbar" :aria-valuenow="current" :aria-valuemax="steps">
      <span
        v-for="step in steps"
        :key="step"
        class="h-[5px] flex-1 rounded-full transition-colors"
        :class="step <= current ? 'bg-accent' : 'bg-hairline'"
      />
    </div>
    <p class="text-[12.5px] font-semibold text-ink-soft">
      Шаг {{ current }} из {{ steps }}<template v-if="stepLabel"> · {{ stepLabel }}</template>
    </p>
  </div>
</template>
