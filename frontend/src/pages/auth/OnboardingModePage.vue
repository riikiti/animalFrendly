<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Building2, ChevronRight, Info, PawPrint, Ribbon } from 'lucide-vue-next'
import BaseStepProgress from '@/shared/ui/components/BaseStepProgress.vue'

const router = useRouter()

/**
 * Режимы — это типы аккаунта, каждый ведёт на свой экран заведения. В макете здесь
 * выбор цели поиска с радиокнопками и отдельной кнопкой «Продолжить»; у нас выбор
 * сразу переносит дальше, поэтому вместо радио — шеврон.
 */
const modes = [
  {
    title: 'Обычный профиль',
    description: 'Знакомства, приюты, покупки',
    route: 'create-pet',
    icon: PawPrint,
    tone: 'bg-accent-soft text-accent-text',
  },
  {
    title: 'Зарегистрировать приют',
    description: 'Пристройство животных',
    route: 'my-shelter',
    icon: Building2,
    tone: 'bg-teal-soft text-teal-text',
  },
  {
    title: 'Стать заводчиком',
    description: 'Продажа щенков/котят',
    route: 'my-breeder',
    icon: Ribbon,
    tone: 'bg-gold-soft text-gold-text',
  },
] as const
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-md flex-col gap-6 px-5 pt-6 pb-8 lg:max-w-lg">
    <BaseStepProgress :steps="2" :current="1" step-label="Кто вы" />

    <div class="flex flex-col gap-1.5">
      <h1 class="font-display text-[28px] leading-tight font-bold text-ink">Что дальше?</h1>
      <p class="text-sm text-ink-soft">
        Выберите, с чего начать — остальное всегда можно завести позже через профиль.
      </p>
    </div>

    <div class="flex flex-col gap-3">
      <button
        v-for="mode in modes"
        :key="mode.route"
        type="button"
        class="flex items-center gap-3.5 rounded-card border border-hairline bg-surface p-3.5 text-left transition-colors hover:border-accent hover:bg-accent-soft"
        @click="router.push({ name: mode.route })"
      >
        <span class="grid size-[46px] shrink-0 place-items-center rounded-2xl" :class="mode.tone">
          <component :is="mode.icon" class="size-[23px]" aria-hidden="true" />
        </span>
        <span class="flex min-w-0 flex-1 flex-col gap-0.5">
          <span class="font-display text-[15.5px] font-bold text-ink">{{ mode.title }}</span>
          <span class="text-[12.5px] text-ink-soft">{{ mode.description }}</span>
        </span>
        <ChevronRight class="size-5 shrink-0 text-ink-faint" aria-hidden="true" />
      </button>
    </div>

    <p class="flex items-center gap-2 text-[12.5px] text-ink-faint">
      <Info class="size-3.5 shrink-0" aria-hidden="true" />
      Дальше добавим анкету питомца
    </p>
  </div>
</template>
