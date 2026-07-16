<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const modes = [
  {
    title: 'Обычный профиль',
    description: 'Знакомства, приюты, покупки',
    route: 'create-pet',
  },
  {
    title: 'Зарегистрировать приют',
    description: 'Пристройство животных',
    route: 'my-shelter',
  },
  {
    title: 'Стать заводчиком',
    description: 'Продажа щенков/котят',
    route: 'my-listings',
  },
] as const
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-6 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex flex-col gap-1 px-2">
      <span class="font-display text-lg text-ink">Что дальше?</span>
      <p class="text-xs text-ink-faint">
        Выберите, с чего начать — остальное всегда можно завести позже через профиль.
      </p>
    </div>

    <div class="flex flex-col gap-2 px-2">
      <button
        v-for="mode in modes"
        :key="mode.route"
        type="button"
        class="flex items-center gap-3 rounded-2xl border border-hairline px-3.5 py-3 text-left transition hover:bg-surface-soft"
        @click="router.push({ name: mode.route })"
      >
        <span>
          <span class="block text-sm font-semibold text-ink">{{ mode.title }}</span>
          <span class="block text-xs text-ink-faint">{{ mode.description }}</span>
        </span>
      </button>
    </div>
  </div>
</template>
