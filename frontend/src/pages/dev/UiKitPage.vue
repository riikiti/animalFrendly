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
  SearchX,
  SlidersHorizontal,
  Syringe,
  Trash2,
  Wallet,
  WifiOff,
  X,
  Zap,
} from 'lucide-vue-next'
import { pushToast } from '@/shared/lib/toast'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'
import BaseAvatarStack from '@/shared/ui/components/BaseAvatarStack.vue'
import BaseBadge from '@/shared/ui/components/BaseBadge.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseChip from '@/shared/ui/components/BaseChip.vue'
import BaseCarouselDots from '@/shared/ui/components/BaseCarouselDots.vue'
import BaseCheckRow from '@/shared/ui/components/BaseCheckRow.vue'
import BaseCoachMark from '@/shared/ui/components/BaseCoachMark.vue'
import BaseCounter from '@/shared/ui/components/BaseCounter.vue'
import BaseEmptyState from '@/shared/ui/components/BaseEmptyState.vue'
import BaseLoadMore from '@/shared/ui/components/BaseLoadMore.vue'
import BaseModal from '@/shared/ui/components/BaseModal.vue'
import BaseMoneyRow from '@/shared/ui/components/BaseMoneyRow.vue'
import BaseOtpInput from '@/shared/ui/components/BaseOtpInput.vue'
import BasePagination from '@/shared/ui/components/BasePagination.vue'
import BasePaymentMethod from '@/shared/ui/components/BasePaymentMethod.vue'
import BasePopover from '@/shared/ui/components/BasePopover.vue'
import BaseProgress from '@/shared/ui/components/BaseProgress.vue'
import BaseRangeSlider from '@/shared/ui/components/BaseRangeSlider.vue'
import BaseRating from '@/shared/ui/components/BaseRating.vue'
import BaseSheet from '@/shared/ui/components/BaseSheet.vue'
import BaseSkeleton from '@/shared/ui/components/BaseSkeleton.vue'
import BaseSpinner from '@/shared/ui/components/BaseSpinner.vue'
import BaseStatusDot from '@/shared/ui/components/BaseStatusDot.vue'
import BaseStatusStep from '@/shared/ui/components/BaseStatusStep.vue'
import BaseStepProgress from '@/shared/ui/components/BaseStepProgress.vue'
import BaseTypingDots from '@/shared/ui/components/BaseTypingDots.vue'
import BottomNav from '@/widgets/BottomNav.vue'
import BaseTooltip from '@/shared/ui/components/BaseTooltip.vue'
import BaseCheckbox from '@/shared/ui/components/BaseCheckbox.vue'
import BaseFab from '@/shared/ui/components/BaseFab.vue'
import BaseIconButton from '@/shared/ui/components/BaseIconButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BasePhotoUpload from '@/shared/ui/components/BasePhotoUpload.vue'
import BaseRadio from '@/shared/ui/components/BaseRadio.vue'
import BaseSearchInput from '@/shared/ui/components/BaseSearchInput.vue'
import BaseSegmented from '@/shared/ui/components/BaseSegmented.vue'
import BaseSelect from '@/shared/ui/components/BaseSelect.vue'
import BaseSelectMenu from '@/shared/ui/components/BaseSelectMenu.vue'
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

const otp = ref('12')
const distance = ref<[number, number]>([5, 30])
const coachShown = ref(true)

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

const overlay = ref<'confirm' | 'delete' | 'wide' | 'sheet' | null>(null)
const page = ref(4)
const slide = ref(0)
const payment = ref('card')
const checks = ref({ received: true, matches: true, intact: false })

const demoToasts = [
  { tone: 'success' as const, title: 'Взаимная симпатия!', description: 'Луна тоже вас лайкнула — напишите первым', actionLabel: 'Открыть' },
  { tone: 'error' as const, title: 'Не удалось отправить', description: 'Проверьте подключение и попробуйте снова', actionLabel: 'Повторить' },
  { tone: 'info' as const, title: 'Анкета на модерации', description: 'Обычно проверка занимает до 2 часов' },
  { tone: 'warning' as const, title: 'Осталось 2 лайка', description: 'Лимит обновится через 4 часа', actionLabel: 'Про PRO' },
  { tone: 'compact' as const, title: 'Анкета скрыта', actionLabel: 'Отменить' },
  { tone: 'loading' as const, title: 'Загружаем фото…', description: '3 из 5 · 64 %', timeout: 3000 },
]

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
            <BaseSelectMenu
              v-model="form.breed"
              label="Порода · рисованный список"
              placeholder="Порода не выбрана"
              :options="breeds"
            >
              <template #lead><Dog class="size-[19px] text-ink-faint" /></template>
            </BaseSelectMenu>
            <BaseOtpInput v-model="otp" label="Код из СМС" />
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
            <BaseRangeSlider
              v-model="distance"
              label="Расстояние"
              :max="50"
              :value-label="`${distance[0]} — ${distance[1]} км`"
              min-label="0"
              max-label="50 км"
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

    <section class="mb-12">
      <h2 class="mb-1 font-display text-xl font-bold">D · Тосты, модалки, шторки</h2>
      <p class="mb-6 text-sm text-ink-soft">Борд D из elements-v2.pen</p>

      <div class="space-y-8">
        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">
            Тосты — нажмите, уведомление всплывёт снизу
          </h3>
          <div class="flex flex-wrap gap-2.5">
            <BaseButton
              v-for="demo in demoToasts"
              :key="demo.title"
              variant="outline"
              size="sm"
              @click="pushToast(demo)"
            >
              {{ demo.title }}
            </BaseButton>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Инлайн-алерты</h3>
          <div class="grid gap-3 sm:grid-cols-2">
            <BaseAlert tone="success" title="Заявка отправлена"
              >Приют ответит в течение 2 дней</BaseAlert
            >
            <BaseAlert tone="warning" title="Подтвердите телефон">
              Без подтверждения нельзя писать владельцам
              <template #action>
                <a href="#" class="text-[12.5px] font-bold underline">Подтвердить сейчас</a>
              </template>
            </BaseAlert>
            <BaseAlert tone="error" title="Оплата не прошла"
              >Банк отклонил операцию. Попробуйте другую карту</BaseAlert
            >
            <BaseAlert title="Анкета видна не всем">Заполните профиль до конца</BaseAlert>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Модалки, шторки и тултипы</h3>
          <div class="flex flex-wrap items-center gap-2.5">
            <BaseButton variant="outline" size="sm" @click="overlay = 'confirm'"
              >Подтверждение</BaseButton
            >
            <BaseButton variant="outline" size="sm" @click="overlay = 'delete'"
              >Опасное действие</BaseButton
            >
            <BaseButton variant="outline" size="sm" @click="overlay = 'wide'"
              >Широкая с формой</BaseButton
            >
            <BaseButton variant="outline" size="sm" @click="overlay = 'sheet'">Шторка</BaseButton>
            <BaseTooltip text="Суперлайк заметят первым">
              <BaseChip tone="outline" size="md" interactive>Наведите — тултип</BaseChip>
            </BaseTooltip>
            <BasePopover title="Проверенный профиль">
              <template #trigger>
                <BaseChip tone="outline" size="md" interactive>Нажмите — поповер</BaseChip>
              </template>
              Мы сверили паспорт владельца и ветеринарные документы питомца.
              <template #footer>
                <a href="#" class="text-[12.5px] font-bold text-accent-text"
                  >Подробнее о верификации</a
                >
              </template>
            </BasePopover>
          </div>

          <div class="mt-4 max-w-md">
            <BaseCoachMark
              v-if="coachShown"
              title="Свайпайте вправо"
              @dismiss="coachShown = false"
            >
              <template #icon><Heart class="size-5" /></template>
              Понравилась анкета — тяните карточку вправо. Влево, чтобы пропустить.
            </BaseCoachMark>
            <BaseButton v-else variant="ghost" size="sm" @click="coachShown = true"
              >Показать подсказку снова</BaseButton
            >
          </div>
        </div>
      </div>
    </section>

    <section class="mb-12">
      <h2 class="mb-1 font-display text-xl font-bold">E · Загрузка, пустоты, ошибки, пагинация</h2>
      <p class="mb-6 text-sm text-ink-soft">Борд E из elements-v2.pen</p>

      <div class="space-y-8">
        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Пагинация и подгрузка</h3>
          <div class="flex flex-wrap items-center gap-6">
            <BasePagination v-model:page="page" :pages="12" />
            <BaseCarouselDots v-model:current="slide" :total="4" />
            <BaseLoadMore :count="20" @load="() => {}" />
            <BaseLoadMore loading loading-label="Загружаем ещё анкеты…" />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Прогресс</h3>
          <div class="grid items-start gap-6 sm:grid-cols-3">
            <BaseProgress
              :value="20"
              :max="128"
              label="Просмотрено анкет"
              value-label="20 из 128"
            />
            <BaseStepProgress :steps="4" :current="2" step-label="Фото питомца" />
            <BaseProgress indeterminate label="Синхронизация" />
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Скелетоны, спиннеры, точки</h3>
          <div class="grid items-start gap-6 sm:grid-cols-3">
            <div class="flex flex-col gap-3.5 rounded-card border border-hairline bg-surface p-3.5">
              <BaseSkeleton variant="block" width="full" height="150px" />
              <div class="flex flex-col gap-2.5">
                <BaseSkeleton width="180px" height="16px" />
                <BaseSkeleton width="240px" />
                <BaseSkeleton width="120px" />
              </div>
              <div class="flex gap-2">
                <BaseSkeleton width="72px" height="26px" variant="circle" />
                <BaseSkeleton width="96px" height="26px" variant="circle" />
              </div>
            </div>

            <div class="divide-y divide-hairline rounded-card border border-hairline bg-surface">
              <div v-for="row in 4" :key="row" class="flex items-center gap-3 px-4 py-3.5">
                <BaseSkeleton variant="circle" width="44px" height="44px" />
                <div class="flex flex-1 flex-col gap-2">
                  <BaseSkeleton width="60%" />
                  <BaseSkeleton width="85%" />
                </div>
                <BaseSkeleton width="34px" height="10px" />
              </div>
            </div>

            <div class="flex flex-col items-start gap-5">
              <div class="flex items-center gap-4">
                <BaseSpinner size="sm" />
                <BaseSpinner />
                <BaseSpinner size="lg" />
                <BaseSpinner tone="ink" />
              </div>
              <BaseTypingDots />
            </div>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">Пустые состояния и ошибки</h3>
          <div class="grid items-start gap-5 sm:grid-cols-3">
            <BaseEmptyState
              title="Пока никто не лайкнул"
              description="Продолжайте смотреть анкеты — взаимные симпатии появятся здесь."
            >
              <template #icon><Heart class="size-8" /></template>
              <template #actions><BaseButton>В ленту</BaseButton></template>
            </BaseEmptyState>

            <BaseEmptyState
              tone="neutral"
              title="Ничего не нашлось"
              description="Попробуйте расширить радиус поиска или снять часть фильтров."
            >
              <template #icon><SearchX class="size-8" /></template>
              <template #actions>
                <BaseButton variant="ghost">Сбросить фильтры</BaseButton>
              </template>
            </BaseEmptyState>

            <BaseEmptyState
              tone="danger"
              title="Нет интернета"
              description="Проверьте подключение — мы попробуем загрузить всё снова."
            >
              <template #icon><WifiOff class="size-8" /></template>
              <template #actions><BaseButton variant="outline">Повторить</BaseButton></template>
            </BaseEmptyState>
          </div>
        </div>
      </div>
    </section>

    <section class="mb-12">
      <h2 class="mb-1 font-display text-xl font-bold">F · Оболочка и строки сделки</h2>
      <p class="mb-6 text-sm text-ink-soft">Tab Bar и компоненты сделки из макета</p>

      <div class="grid items-start gap-6 sm:grid-cols-3">
        <div class="flex flex-col gap-4 rounded-card border border-hairline bg-surface p-4">
          <h3 class="text-sm font-semibold text-ink-soft">Расчёт сделки</h3>
          <BaseMoneyRow label="Товар" value="1 290 ₽" />
          <BaseMoneyRow label="Доставка · СДЭК" value="200 ₽" />
          <BaseMoneyRow label="Комиссия площадки · 5 %" value="−64,50 ₽" negative />
          <div class="border-t border-hairline pt-3.5">
            <BaseMoneyRow label="Итого" value="1 490 ₽" variant="total" />
          </div>
        </div>

        <div class="flex flex-col gap-3.5 rounded-card border border-hairline bg-surface p-4">
          <h3 class="text-sm font-semibold text-ink-soft">Подтверждение получения</h3>
          <BaseCheckRow v-model:checked="checks.received" interactive
            >Товар получен и распакован</BaseCheckRow
          >
          <BaseCheckRow v-model:checked="checks.matches" interactive
            >Соответствует описанию и фото</BaseCheckRow
          >
          <BaseCheckRow v-model:checked="checks.intact" interactive
            >Нет повреждений и брака</BaseCheckRow
          >

          <h3 class="mt-2 text-sm font-semibold text-ink-soft">Способ оплаты</h3>
          <BasePaymentMethod
            title="Карта •••• 4242"
            description="Списание через ЮKassa"
            :selected="payment === 'card'"
            @select="payment = 'card'"
          >
            <template #icon><Wallet class="size-[17px]" /></template>
          </BasePaymentMethod>
          <BasePaymentMethod
            title="СБП"
            description="Оплата по QR-коду"
            :selected="payment === 'sbp'"
            @select="payment = 'sbp'"
          >
            <template #icon><Zap class="size-[17px]" /></template>
          </BasePaymentMethod>
        </div>

        <div class="flex flex-col gap-1 rounded-card border border-hairline bg-surface p-4">
          <h3 class="mb-3 text-sm font-semibold text-ink-soft">История заказа</h3>
          <BaseStatusStep state="done" title="Оплачено · деньги на эскроу" meta="14 марта, 12:40" />
          <BaseStatusStep state="done" title="Продавец подтвердил заказ" meta="14 марта, 13:05" />
          <BaseStatusStep state="current" title="Передан в СДЭК" meta="15 марта, 09:20" />
          <BaseStatusStep title="Подтверждение получения" meta="Осталось 6 дней" last />
        </div>
      </div>

      <h3 class="mt-8 mb-3 text-sm font-semibold text-ink-soft">
        Нижняя навигация — прокрутите рамку, панель остаётся на месте
      </h3>
      <div
        class="flex h-[320px] w-[390px] max-w-full flex-col overflow-y-auto rounded-card border border-hairline bg-bg"
      >
        <div class="flex-1 space-y-3 p-4">
          <div v-for="row in 6" :key="row" class="flex items-center gap-3">
            <BaseSkeleton variant="circle" width="44px" height="44px" />
            <div class="flex flex-1 flex-col gap-2">
              <BaseSkeleton width="55%" />
              <BaseSkeleton width="80%" />
            </div>
          </div>
        </div>
        <BottomNav />
      </div>
    </section>

    <BaseModal
      :open="overlay === 'confirm'"
      title="Отправить заявку на Барсика?"
      description="Приют «Верный друг» получит вашу анкету и свяжется в течение двух дней."
      @close="overlay = null"
    >
      <template #icon><PawPrint class="size-6" /></template>
      <template #actions>
        <BaseButton size="lg" block @click="overlay = null">Отправить заявку</BaseButton>
        <BaseButton variant="ghost" block @click="overlay = null">Отмена</BaseButton>
      </template>
    </BaseModal>

    <BaseModal
      :open="overlay === 'delete'"
      tone="danger"
      title="Удалить анкету Луны?"
      description="Мэтчи и переписки будут удалены без возможности восстановления."
      @close="overlay = null"
    >
      <template #icon><Trash2 class="size-6" /></template>
      <template #actions>
        <BaseButton variant="danger" size="lg" block @click="overlay = null">Удалить</BaseButton>
        <BaseButton variant="ghost" block @click="overlay = null">Оставить</BaseButton>
      </template>
    </BaseModal>

    <BaseModal
      :open="overlay === 'wide'"
      wide
      closable
      title="Новое объявление"
      description="Заполните основное — остальное можно добавить позже"
      @close="overlay = null"
    >
      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput v-model="form.filled" label="Кличка" />
        <BaseInput v-model="form.price" label="Цена" suffix="₽" />
        <BaseSelect v-model="form.breed" label="Порода" :options="breeds" class="sm:col-span-2" />
      </div>
      <template #actions>
        <BaseButton variant="outline" @click="overlay = null">Отмена</BaseButton>
        <BaseButton @click="overlay = null">Опубликовать</BaseButton>
      </template>
    </BaseModal>

    <BaseSheet :open="overlay === 'sheet'" title="Фильтры" closable @close="overlay = null">
      <div class="flex flex-col gap-4 pb-2">
        <BaseSegmented
          v-model="form.tab"
          aria-label="Вид"
          :options="[
            { value: 'all', label: 'Все' },
            { value: 'dogs', label: 'Собаки' },
            { value: 'cats', label: 'Кошки' },
          ]"
        />
        <BaseSlider
          v-model="form.age"
          label="Возраст"
          :max="15"
          :value-label="`до ${form.age} лет`"
        />
        <BaseSwitch v-model="form.notify" label="Только проверенные" />
      </div>
      <template #actions>
        <BaseButton size="lg" block @click="overlay = null">Показать 128 анкет</BaseButton>
      </template>
    </BaseSheet>
  </div>
</template>
