<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import * as catalogApi from '@/entities/catalog/api'
import { useCatalogStore } from '@/entities/catalog/model'
import type { Breed } from '@/entities/catalog/types'
import * as petApi from '@/entities/pet/api'
import { usePetStore } from '@/entities/pet/model'
import type { PetPhoto } from '@/entities/pet/types'
import { ApiError } from '@/shared/api/http'

const MAX_PHOTOS = 6

const router = useRouter()
const catalogStore = useCatalogStore()
const petStore = usePetStore()

const purposes = [
  { value: 'social', title: 'Общение' },
  { value: 'breeding', title: 'Вязка' },
] as const

const form = reactive({
  speciesId: null as number | null,
  breedId: null as number | null,
  name: '',
  sex: 'male' as 'male' | 'female',
  purpose: 'social' as (typeof purposes)[number]['value'],
})

const breeds = ref<Breed[]>([])
const isSubmitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

const step = ref<'form' | 'photo'>('form')
const createdPetId = ref<string | null>(null)
const photos = ref<PetPhoto[]>([])
const isUploadingPhoto = ref(false)
const photoError = ref('')

// Пользователь может уйти со страницы, пока справочник видов/пород ещё грузится (см. тот
// же приём в SwipePage.vue) — не считаем это необработанной ошибкой.
let isMounted = true
onUnmounted(() => {
  isMounted = false
})

onMounted(async () => {
  try {
    await catalogStore.ensureSpeciesLoaded()

    const first = catalogStore.species[0]
    if (first) {
      await selectSpecies(first.slug, first.id)
    }
  } catch (error) {
    if (isMounted) throw error
  }
})

async function selectSpecies(slug: string, id: number): Promise<void> {
  form.speciesId = id
  form.breedId = null

  const response = await catalogApi.listBreeds(slug)
  breeds.value = response.data
}

async function onSubmit(): Promise<void> {
  if (form.speciesId === null) return

  errors.value = {}
  generalError.value = ''
  isSubmitting.value = true

  try {
    const pet = await petStore.createPet({
      species_id: form.speciesId,
      breed_id: form.breedId,
      name: form.name,
      sex: form.sex,
      purpose: form.purpose,
    })
    createdPetId.value = pet.id
    step.value = 'photo'
  } catch (error) {
    if (error instanceof ApiError) {
      errors.value = error.errors ?? {}
      if (!error.errors) generalError.value = error.message
    } else {
      generalError.value = 'Что-то пошло не так. Попробуйте ещё раз.'
    }
  } finally {
    isSubmitting.value = false
  }
}

async function refreshPhotos(): Promise<void> {
  if (createdPetId.value === null) return
  const response = await petApi.listPetPhotos(createdPetId.value)
  photos.value = response.data
}

async function onPhotoSelected(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file || createdPetId.value === null) return

  photoError.value = ''
  isUploadingPhoto.value = true

  try {
    await petApi.addPetPhoto(createdPetId.value, file)
    await refreshPhotos()
  } catch (error) {
    photoError.value = error instanceof ApiError ? error.message : 'Не удалось загрузить фото.'
  } finally {
    isUploadingPhoto.value = false
  }
}

async function setCover(photoId: string): Promise<void> {
  if (createdPetId.value === null) return
  await petApi.setPetPhotoCover(createdPetId.value, photoId)
  await refreshPhotos()
}

async function removePhoto(photoId: string): Promise<void> {
  if (createdPetId.value === null) return
  await petApi.removePetPhoto(createdPetId.value, photoId)
  await refreshPhotos()
}

async function finishPhotoStep(): Promise<void> {
  await router.push({ name: 'home' })
}
</script>

<template>
  <form v-if="step === 'form'" class="flex flex-col gap-4" @submit.prevent="onSubmit">
    <BaseInput v-model="form.name" label="Кличка" placeholder="Рекс" :error="errors.name?.[0]" />

    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Вид</span>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="s in catalogStore.species"
          :key="s.id"
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="form.speciesId === s.id ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="selectSpecies(s.slug, s.id)"
        >
          {{ s.name }}
        </button>
      </div>
      <p v-if="errors.species_id" class="text-xs text-danger">{{ errors.species_id[0] }}</p>
    </div>

    <div v-if="breeds.length > 0" class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Порода</span>
      <select
        v-model="form.breedId"
        class="rounded-xl bg-surface-soft px-3.5 py-2.5 text-sm text-ink outline-none"
      >
        <option :value="null">Не указана</option>
        <option v-for="b in breeds" :key="b.id" :value="b.id">{{ b.name }}</option>
      </select>
    </div>

    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Пол</span>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="form.sex === 'male' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="form.sex = 'male'"
        >
          Мальчик
        </button>
        <button
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="form.sex === 'female' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="form.sex = 'female'"
        >
          Девочка
        </button>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Цель анкеты</span>
      <div class="flex gap-2">
        <button
          v-for="p in purposes"
          :key="p.value"
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="
            form.purpose === p.value ? 'bg-accent text-accent-ink' : 'bg-surface-soft text-ink-soft'
          "
          @click="form.purpose = p.value"
        >
          {{ p.title }}
        </button>
      </div>
    </div>

    <p v-if="generalError" class="text-xs text-danger">{{ generalError }}</p>

    <BaseButton type="submit" :disabled="isSubmitting || form.speciesId === null">
      {{ isSubmitting ? 'Создаём анкету…' : 'Создать анкету' }}
    </BaseButton>
  </form>

  <div v-else class="flex flex-col gap-4">
    <span class="text-xs font-semibold text-ink-soft">Фото питомца (до {{ MAX_PHOTOS }})</span>

    <div class="grid grid-cols-3 gap-2">
      <div
        v-for="photo in photos"
        :key="photo.id"
        class="relative aspect-square overflow-hidden rounded-xl bg-surface-soft"
      >
        <img :src="photo.url" class="h-full w-full object-cover" alt="" />
        <span
          v-if="photo.is_primary"
          class="absolute left-1 top-1 rounded-full bg-teal px-1.5 py-0.5 text-[10px] font-semibold text-white"
        >
          Обложка
        </span>
        <button
          v-else
          type="button"
          class="absolute left-1 top-1 rounded-full bg-surface/90 px-1.5 py-0.5 text-[10px] font-semibold text-ink"
          @click="setCover(photo.id)"
        >
          Сделать обложкой
        </button>
        <button
          type="button"
          class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-surface/90 text-xs text-ink"
          @click="removePhoto(photo.id)"
        >
          ✕
        </button>
      </div>

      <label
        v-if="photos.length < MAX_PHOTOS"
        class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-hairline text-ink-faint"
      >
        <span class="text-xl">+</span>
        <span class="text-[10px]">{{ isUploadingPhoto ? 'Загрузка…' : 'Добавить' }}</span>
        <input
          type="file"
          accept="image/*"
          class="hidden"
          :disabled="isUploadingPhoto"
          @change="onPhotoSelected"
        />
      </label>
    </div>

    <p v-if="photoError" class="text-xs text-danger">{{ photoError }}</p>

    <BaseButton @click="finishPhotoStep">Готово</BaseButton>
  </div>
</template>
