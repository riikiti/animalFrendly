<script setup lang="ts">
import { ref, watchEffect } from 'vue'
import { Check, Heart, MessageCircle, Plus, Trash2, X } from 'lucide-vue-next'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseFab from '@/shared/ui/components/BaseFab.vue'
import BaseIconButton from '@/shared/ui/components/BaseIconButton.vue'

/**
 * Витрина библиотеки компонентов. Доступна только в дев-сборке — маршрут заводится
 * в src/app/router/index.ts под флагом import.meta.env.DEV.
 * Пополняется по мере прохождения бордов A–F.
 */
const theme = ref<'system' | 'light' | 'dark'>('system')

watchEffect(() => {
  const root = document.documentElement
  if (theme.value === 'system') root.removeAttribute('data-theme')
  else root.setAttribute('data-theme', theme.value)
})
</script>

<template>
  <div class="min-h-screen bg-bg px-screen py-8 text-ink">
    <header class="mb-10 flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-semibold tracking-widest text-ink-faint uppercase">Лапки</p>
        <h1 class="font-display text-3xl font-bold">Библиотека компонентов</h1>
      </div>
      <div class="flex gap-2" role="group" aria-label="Тема оформления">
        <BaseButton
          v-for="option in ['system', 'light', 'dark'] as const"
          :key="option"
          size="sm"
          :variant="theme === option ? 'primary' : 'outline'"
          @click="theme = option"
        >
          {{ { system: 'Системная', light: 'Светлая', dark: 'Тёмная' }[option] }}
        </BaseButton>
      </div>
    </header>

    <section class="mb-12">
      <h2 class="mb-1 font-display text-xl font-bold">A · Кнопки и действия</h2>
      <p class="mb-6 text-sm text-ink-soft">Борд A из elements-v2.pen</p>

      <div class="space-y-8">
        <div v-for="row in ['primary', 'outline', 'ghost', 'danger'] as const" :key="row">
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">
            {{
              {
                primary: 'Основная — заполненная',
                outline: 'Вторичная — контурная',
                ghost: 'Призрачная — текстовая',
                danger: 'Опасное действие',
              }[row]
            }}
          </h3>
          <div class="flex flex-wrap items-center gap-4">
            <BaseButton :variant="row">Продолжить</BaseButton>
            <BaseButton :variant="row" disabled>Недоступна</BaseButton>
            <BaseButton :variant="row" loading>Отправляем</BaseButton>
            <BaseButton :variant="row">
              <Heart class="size-5" aria-hidden="true" />
              С иконкой
            </BaseButton>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Размеры и ширина</h3>
          <div class="mb-4 flex flex-wrap items-center gap-4">
            <BaseButton size="lg">L · 54</BaseButton>
            <BaseButton size="md">M · 44</BaseButton>
            <BaseButton size="sm">S · 36</BaseButton>
            <BaseButton size="xs">XS · 30</BaseButton>
          </div>
          <div class="max-w-sm space-y-3">
            <BaseButton size="lg" block>Во всю ширину</BaseButton>
            <div class="flex gap-2.5">
              <BaseButton variant="outline" size="lg" block>Отмена</BaseButton>
              <BaseButton size="lg" block>Сохранить</BaseButton>
            </div>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Иконочные кнопки и FAB</h3>
          <div class="flex flex-wrap items-center gap-4">
            <BaseIconButton label="Пропустить" elevated><X class="size-6" /></BaseIconButton>
            <BaseIconButton label="В избранное" tone="active"><Heart class="size-6" /></BaseIconButton>
            <BaseIconButton label="Удалить" tone="danger"><Trash2 class="size-5" /></BaseIconButton>
            <BaseIconButton label="Подтвердить" tone="success"><Check class="size-5" /></BaseIconButton>
            <BaseIconButton label="Недоступно" disabled><X class="size-6" /></BaseIconButton>
            <BaseIconButton label="Сообщения" :badge="3"><MessageCircle class="size-5" /></BaseIconButton>
            <BaseIconButton label="Добавить" shape="square" size="sm"><Plus class="size-4" /></BaseIconButton>
            <BaseFab label="Добавить питомца"><Plus class="size-6" /></BaseFab>
            <BaseFab label="Добавить питомца" extended><Plus class="size-5" /></BaseFab>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
