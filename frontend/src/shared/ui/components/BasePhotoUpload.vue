<script setup lang="ts">
import { ref } from 'vue'
import { ImagePlus, Plus, TriangleAlert, X } from 'lucide-vue-next'

export interface UploadPhoto {
  url: string
  status?: 'ready' | 'uploading' | 'error'
}

/**
 * Загрузка фото по секции «Загрузка фото» борда B: пустая зона с подсказкой,
 * дальше плитки с состояниями и кнопкой добавления. Сама отправка файлов на сервер
 * остаётся на вызывающем экране — компонент только отдаёт выбранные файлы.
 */
const props = withDefaults(
  defineProps<{
    photos: UploadPhoto[]
    title?: string
    hint?: string
    max?: number
    accept?: string
    disabled?: boolean
  }>(),
  {
    title: 'Загрузите фото питомца',
    hint: 'JPG или PNG, до 10 МБ · минимум 2 фото',
    max: 6,
    accept: 'image/*',
    disabled: false,
  },
)

const emit = defineEmits<{ add: [files: File[]]; remove: [index: number] }>()

const dragging = ref(false)

const pick = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files?.length) emit('add', Array.from(input.files))
  // Сбрасываем, иначе повторный выбор того же файла не вызовет change.
  input.value = ''
}

const drop = (event: DragEvent) => {
  dragging.value = false
  if (props.disabled) return
  const files = Array.from(event.dataTransfer?.files ?? []).filter((file) =>
    file.type.startsWith('image/'),
  )
  if (files.length) emit('add', files)
}
</script>

<template>
  <div>
    <label
      v-if="photos.length === 0"
      class="flex h-[170px] flex-col items-center justify-center gap-2.5 rounded-card border-2 border-dashed bg-bg px-5 text-center transition-colors"
      :class="[
        disabled ? 'cursor-not-allowed border-hairline' : 'cursor-pointer border-accent-soft',
        dragging && 'border-accent bg-accent-soft',
      ]"
      @dragover.prevent="dragging = !disabled"
      @dragleave="dragging = false"
      @drop.prevent="drop"
    >
      <span class="grid size-13 place-items-center rounded-full bg-surface">
        <ImagePlus class="size-6 text-accent-text" aria-hidden="true" />
      </span>
      <span class="font-display text-base font-bold text-ink">{{ title }}</span>
      <span class="text-xs text-ink-faint">{{ hint }}</span>
      <input type="file" :accept="accept" :disabled="disabled" class="sr-only" @change="pick" />
    </label>

    <div v-else class="flex flex-wrap gap-3">
      <div
        v-for="(photo, index) in photos"
        :key="photo.url + index"
        class="relative h-[120px] w-[100px] overflow-hidden rounded-2xl"
        :class="photo.status === 'error' ? 'border-[1.5px] border-danger bg-danger-soft' : 'bg-surface-soft'"
      >
        <img
          v-if="photo.status !== 'error'"
          :src="photo.url"
          alt=""
          class="size-full object-cover"
          :class="photo.status === 'uploading' && 'opacity-40'"
        />

        <span
          v-if="photo.status === 'uploading'"
          class="absolute inset-0 grid place-items-center text-[11px] font-semibold text-ink"
          >Загрузка…</span
        >

        <span
          v-else-if="photo.status === 'error'"
          class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 px-2 text-center text-[11px] font-semibold text-danger"
        >
          <TriangleAlert class="size-5" aria-hidden="true" />
          Не загрузилось
        </span>

        <span
          v-else-if="index === 0"
          class="absolute bottom-1.5 left-1.5 rounded-full bg-accent px-2 py-0.5 text-[10px] font-semibold text-accent-ink"
          >Главное</span
        >

        <button
          v-if="!disabled"
          type="button"
          class="absolute top-1.5 right-1.5 grid size-6 place-items-center rounded-full bg-bezel/60 text-white transition hover:bg-bezel/80"
          :aria-label="`Удалить фото ${index + 1}`"
          @click="emit('remove', index)"
        >
          <X class="size-3.5" stroke-width="2.5" />
        </button>
      </div>

      <label
        v-if="photos.length < max && !disabled"
        class="flex h-[120px] w-[100px] cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border-2 border-hairline bg-surface text-ink-soft transition-colors hover:border-accent hover:text-accent-text"
      >
        <Plus class="size-6" aria-hidden="true" />
        <span class="text-[11px] font-semibold">Добавить</span>
        <input type="file" :accept="accept" class="sr-only" @change="pick" />
      </label>
    </div>
  </div>
</template>
