<script setup lang="ts">
import { ref, watchEffect } from 'vue'
import {
  BadgeCheck,
  Cake,
  Check,
  Dog,
  Heart,
  MapPin,
  MessageCircle,
  PawPrint,
  Plus,
  SlidersHorizontal,
  Syringe,
  Trash2,
  Wallet,
  X,
  Zap,
} from 'lucide-vue-next'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'
import BaseAvatarStack from '@/shared/ui/components/BaseAvatarStack.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseChip from '@/shared/ui/components/BaseChip.vue'
import BaseCounter from '@/shared/ui/components/BaseCounter.vue'
import BaseRating from '@/shared/ui/components/BaseRating.vue'
import BaseStatusDot from '@/shared/ui/components/BaseStatusDot.vue'
import BaseCheckbox from '@/shared/ui/components/BaseCheckbox.vue'
import BaseFab from '@/shared/ui/components/BaseFab.vue'
import BaseIconButton from '@/shared/ui/components/BaseIconButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BasePhotoUpload from '@/shared/ui/components/BasePhotoUpload.vue'
import BaseRadio from '@/shared/ui/components/BaseRadio.vue'
import BaseSearchInput from '@/shared/ui/components/BaseSearchInput.vue'
import BaseSegmented from '@/shared/ui/components/BaseSegmented.vue'
import BaseSelect from '@/shared/ui/components/BaseSelect.vue'
import BaseSlider from '@/shared/ui/components/BaseSlider.vue'
import BaseStepper from '@/shared/ui/components/BaseStepper.vue'
import BaseSwitch from '@/shared/ui/components/BaseSwitch.vue'
import BaseTextarea from '@/shared/ui/components/BaseTextarea.vue'

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

const form = ref({
  empty: '',
  filled: 'Луна',
  wrong: '89',
  search: 'корги',
  password: 'секрет',
  phone: '999 123-45-67',
  price: '4500',
  breed: '',
  about: 'Луна обожает долгие прогулки в парке и знакомства с новыми друзьями.',
  agree: true,
  partial: false,
  sex: 'female',
  notify: true,
  geo: false,
  tab: 'all',
  age: 5,
  count: 2,
})

const breeds = [
  { value: 'corgi', label: 'Вельш-корги' },
  { value: 'shiba', label: 'Сиба-ину' },
  { value: 'maine-coon', label: 'Мейн-кун' },
]

// Заглушки рисуем прямо в разметке, чтобы витрина не ходила в сеть за картинками.
const stub = (color: string) =>
  `data:image/svg+xml;utf8,${encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="120"><rect width="100" height="120" fill="${color}"/></svg>`,
  )}`

const avatarSizes = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'] as const

const photos = ref([
  { url: stub('#F5B23E'), status: 'ready' as const },
  { url: stub('#12B3A6'), status: 'uploading' as const },
  { url: '', status: 'error' as const },
])
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

    <section class="mb-12">
      <h2 class="mb-1 font-display text-xl font-bold">B · Поля ввода и формы</h2>
      <p class="mb-6 text-sm text-ink-soft">Борд B из elements-v2.pen</p>

      <div class="space-y-8">
        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Текстовое поле · состояния</h3>
          <div class="grid gap-5 sm:grid-cols-3">
            <BaseInput v-model="form.empty" label="Пустое" placeholder="Кличка питомца" />
            <BaseInput v-model="form.filled" label="Заполнено" />
            <BaseInput v-model="form.filled" label="Успех" success />
            <BaseInput v-model="form.wrong" label="Ошибка" error="Введите не меньше 2 символов" />
            <BaseInput
              v-model="form.filled"
              label="С подсказкой"
              hint="Так питомца увидят в ленте"
            />
            <BaseInput v-model="form.filled" label="Недоступно" disabled />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Типы полей</h3>
          <div class="grid gap-5 sm:grid-cols-3">
            <BaseSearchInput v-model="form.search" label="Поиск" placeholder="Кличка или порода" />
            <BaseInput v-model="form.password" label="Пароль" type="password" />
            <BaseInput
              v-model="form.phone"
              label="Телефон"
              type="tel"
              prefix="+7"
              inputmode="tel"
              success
            />
            <BaseInput v-model="form.price" label="Цена" suffix="₽" inputmode="numeric">
              <template #lead><Wallet class="size-[19px] text-ink-faint" /></template>
            </BaseInput>
            <BaseSelect
              v-model="form.breed"
              label="Порода"
              placeholder="Порода не выбрана"
              :options="breeds"
            >
              <template #lead><Dog class="size-[19px] text-ink-faint" /></template>
            </BaseSelect>
            <BaseTextarea
              v-model="form.about"
              label="Многострочное"
              :rows="3"
              :maxlength="200"
              class="sm:col-span-2"
            />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Переключатели и выбор</h3>
          <div class="grid items-start gap-6 sm:grid-cols-2">
            <div class="space-y-3">
              <BaseCheckbox v-model="form.agree">Согласен с условиями сервиса</BaseCheckbox>
              <BaseCheckbox v-model="form.partial" indeterminate>Выбрана часть пород</BaseCheckbox>
              <BaseCheckbox v-model="form.partial" error>С ошибкой</BaseCheckbox>
              <BaseCheckbox v-model="form.agree" disabled>Недоступно</BaseCheckbox>
            </div>
            <div class="space-y-3">
              <BaseRadio v-model="form.sex" name="sex" value="female">Девочка</BaseRadio>
              <BaseRadio v-model="form.sex" name="sex" value="male">Мальчик</BaseRadio>
              <BaseRadio v-model="form.sex" name="sex" value="none" disabled>Недоступно</BaseRadio>
            </div>
            <div class="rounded-card border border-hairline bg-surface">
              <div class="border-b border-hairline px-4 py-3.5">
                <BaseSwitch v-model="form.notify" label="Уведомления о лайках" />
              </div>
              <div class="px-4 py-3.5">
                <BaseSwitch
                  v-model="form.geo"
                  label="Показывать геолокацию"
                  description="Виден только город"
                />
              </div>
            </div>
            <BaseSegmented
              v-model="form.tab"
              aria-label="Фильтр"
              :options="[
                { value: 'all', label: 'Все' },
                { value: 'dogs', label: 'Собаки' },
                { value: 'cats', label: 'Кошки' },
              ]"
            />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Слайдеры и счётчики</h3>
          <div class="grid items-start gap-6 sm:grid-cols-2">
            <BaseSlider
              v-model="form.age"
              label="Возраст"
              :max="15"
              :value-label="`до ${form.age} лет`"
              min-label="0"
              max-label="15 лет"
            />
            <BaseStepper v-model="form.count" aria-label="Количество" :max="10" />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Загрузка фото</h3>
          <div class="grid items-start gap-6 sm:grid-cols-2">
            <BasePhotoUpload :photos="[]" />
            <BasePhotoUpload :photos="photos" @remove="photos.splice($event, 1)" />
          </div>
        </div>
      </div>
    </section>

    <section class="mb-12">
      <h2 class="mb-1 font-display text-xl font-bold">C · Чипы, бейджи, аватары</h2>
      <p class="mb-6 text-sm text-ink-soft">Борд C из elements-v2.pen</p>

      <div class="space-y-8">
        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Чипы · состояния</h3>
          <div class="flex flex-wrap items-center gap-2.5">
            <BaseChip>
              <template #icon><PawPrint class="size-[15px]" /></template>
              Порода
            </BaseChip>
            <BaseChip tone="accent">
              <template #icon><Check class="size-[15px]" /></template>
              Выбран
            </BaseChip>
            <BaseChip tone="soft">
              <template #icon><MapPin class="size-[15px]" /></template>
              Рядом
            </BaseChip>
            <BaseChip tone="outline">Контурный</BaseChip>
            <BaseChip disabled>Недоступен</BaseChip>
            <BaseChip tone="ink" :count="24">
              <template #icon><Zap class="size-[15px]" /></template>
              Активные
            </BaseChip>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-2.5 rounded-card bg-bezel p-3">
            <BaseChip tone="glass">Активная сейчас</BaseChip>
            <BaseChip tone="glass">Любит парк</BaseChip>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Фильтры</h3>
          <div class="flex flex-wrap items-center gap-2.5">
            <BaseChip tone="ink" size="md" interactive :count="3">
              <template #icon><SlidersHorizontal class="size-[15px]" /></template>
              Фильтры
            </BaseChip>
            <BaseChip tone="soft" size="md" interactive removable>
              <template #icon><MapPin class="size-[15px]" /></template>
              Рядом · 5 км
            </BaseChip>
            <BaseChip tone="soft" size="md" interactive removable>
              <template #icon><Cake class="size-[15px]" /></template>
              До 3 лет
            </BaseChip>
            <BaseChip tone="outline" size="md" interactive>
              <template #icon><Syringe class="size-[15px]" /></template>
              Привиты
            </BaseChip>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Бейджи</h3>
          <div class="flex flex-wrap items-center gap-2.5">
            <BaseBadge>
              <template #icon><BadgeCheck class="size-3.5" /></template>
              Проверен
            </BaseBadge>
            <BaseBadge tone="teal">Привит</BaseBadge>
            <BaseBadge tone="info">Чипирован</BaseBadge>
            <BaseBadge tone="gold">Родословная РКФ</BaseBadge>
            <BaseBadge tone="accent">Приют-партнёр</BaseBadge>
            <BaseBadge tone="neutral">Не подтверждён</BaseBadge>
            <BaseBadge tone="gold">На модерации</BaseBadge>
            <BaseBadge tone="danger">Отклонено</BaseBadge>
            <BaseBadge tone="accent" solid>Пристроен</BaseBadge>
            <BaseBadge tone="danger" solid>Срочно нужен дом</BaseBadge>
            <BaseBadge tone="neutral" solid size="md">
              <template #icon><BadgeCheck class="size-[17px] text-teal" /></template>
              Профиль подтверждён
            </BaseBadge>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Индикаторы и счётчики</h3>
          <div class="flex flex-wrap items-center gap-5">
            <BaseStatusDot>Онлайн</BaseStatusDot>
            <BaseStatusDot tone="recent">Недавно</BaseStatusDot>
            <BaseStatusDot tone="offline">Не в сети</BaseStatusDot>
            <BaseCounter :value="3" />
            <BaseCounter :value="12" />
            <BaseCounter :value="128" tone="danger" />
            <BaseCounter value="NEW" tone="ink" />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Аватары</h3>
          <div class="mb-4 flex flex-wrap items-end gap-3.5">
            <BaseAvatar v-for="s in avatarSizes" :key="s" :size="s" name="Луна Хаски" />
          </div>
          <div class="flex flex-wrap items-center gap-4">
            <BaseAvatar size="lg" :src="stub('#FF6B4A')" name="Луна" presence="online" />
            <BaseAvatar size="lg" name="Луна Хаски" />
            <BaseAvatar size="lg" name="Добрые лапы" shape="rounded" />
            <BaseAvatar size="lg" presence="offline" />
            <BaseAvatar size="lg" name="Луна Хаски" verified />
            <BaseAvatarStack
              :people="[{ name: 'Аня К' }, { name: 'Борис' }, { name: 'Вера' }, {}, {}]"
            />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Рейтинг и отзывы</h3>
          <div class="flex flex-wrap items-center gap-6">
            <BaseRating :value="4.8" :count="126" />
            <BaseRating :value="4.9" compact note="Топ продавец" />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
