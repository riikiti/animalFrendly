<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Star, X } from 'lucide-vue-next'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseChip from '@/shared/ui/components/BaseChip.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BaseSelectMenu from '@/shared/ui/components/BaseSelectMenu.vue'
import BaseTextarea from '@/shared/ui/components/BaseTextarea.vue'
import PaywallSheet from '@/shared/ui/components/PaywallSheet.vue'
import * as catalogApi from '@/entities/catalog/api'
import { useCatalogStore } from '@/entities/catalog/model'
import type { Breed } from '@/entities/catalog/types'
import * as petApi from '@/entities/pet/api'
import { usePetStore } from '@/entities/pet/model'
import { socialTagOptions } from '@/entities/pet/socialTags'
import type { PetPhoto, PetSocialTag } from '@/entities/pet/types'
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
  description: '',
  socialTags: [] as PetSocialTag[],
})

const breeds = ref<Breed[]>([])
const isSubmitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')
const paywallOpen = ref(false)
const paywallMessage = ref('')

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

function toggleTag(tag: PetSocialTag): void {
  const index = form.socialTags.indexOf(tag)
  if (index === -1) {
    form.socialTags.push(tag)
  } else {
    form.socialTags.splice(index, 1)
  }
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
      description: form.description.trim() || null,
      social_tags: form.socialTags,
    })
    createdPetId.value = pet.id
    step.value = 'photo'
  } catch (error) {
    if (error instanceof ApiError && error.status === 402) {
      paywallMessage.value = error.message
      paywallOpen.value = true
    } else if (error instanceof ApiError) {
      errors.value = error.errors ?? {}
      if (!error.errors) generalError.value = error.message
    } else {
      generalError.value = 'Что-то пошло не так. Попробуйте ещё раз.'
    }
  } finally {
    isSubmitting.value = false
  }
}

function closePaywall(): void {
  paywallOpen.value = false
}

async function goToSubscriptionPlans(): Promise<void> {
  paywallOpen.value = false
  await router.push({ name: 'subscription-plans' })
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
        <BaseChip
          v-for="s in catalogStore.species"
          :key="s.id"
          interactive
          size="md"
          :tone="form.speciesId === s.id ? 'accent' : 'outline'"
          @click="selectSpecies(s.slug, s.id)"
        >
          {{ s.name }}
        </BaseChip>
      </div>
      <p v-if="errors.species_id" class="text-xs text-danger">{{ errors.species_id[0] }}</p>
    </div>

    <BaseSelectMenu
      v-if="breeds.length > 0"
      v-model="form.breedId"
      label="Порода"
      placeholder="Не указана"
      :options="breeds.map((b) => ({ value: b.id, label: b.name }))"
    />

    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Пол</span>
      <div class="flex gap-2">
        <BaseChip
          interactive
          size="md"
          :tone="form.sex === 'male' ? 'accent' : 'outline'"
          @click="form.sex = 'male'"
        >
          Мальчик
        </BaseChip>
        <BaseChip
          interactive
          size="md"
          :tone="form.sex === 'female' ? 'accent' : 'outline'"
          @click="form.sex = 'female'"
        >
          Девочка
        </BaseChip>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Цель анкеты</span>
      <div class="flex flex-wrap gap-2">
        <BaseChip
          v-for="p in purposes"
          :key="p.value"
          interactive
          size="md"
          :tone="form.purpose === p.value ? 'accent' : 'outline'"
          @click="form.purpose = p.value"
        >
          {{ p.title }}
        </BaseChip>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Для чего ищу общение</span>
      <div class="flex flex-wrap gap-2">
        <BaseChip
          v-for="tag in socialTagOptions"
          :key="tag.value"
          interactive
          size="md"
          :tone="form.socialTags.includes(tag.value) ? 'accent' : 'outline'"
          @click="toggleTag(tag.value)"
        >
          {{ tag.title }}
        </BaseChip>
      </div>
    </div>

    <BaseTextarea
      v-model="form.description"
      label="О питомце"
      :rows="3"
      placeholder="Расскажите о характере, привычках…"
    />

    <p v-if="generalError" class="text-xs text-danger">{{ generalError }}</p>

    <BaseButton
      type="submit"
      size="lg"
      block
      class="mt-1"
      :loading="isSubmitting"
      :disabled="form.speciesId === null"
    >
      {{ isSubmitting ? 'Создаём анкету…' : 'Создать анкету' }}
    </BaseButton>
  </form>

  <div v-else class="flex flex-col gap-4">
    <span class="text-xs font-semibold text-ink-soft">Фото питомца (до {{ MAX_PHOTOS }})</span>

    <div class="grid grid-cols-3 gap-2">
      <div
        v-for="photo in photos"
        :key="photo.id"
        class="relative aspect-square overflow-hidden rounded-2xl bg-surface-soft"
      >
        <img :src="photo.url" class="size-full object-cover" alt="" />
        <span
          v-if="photo.is_primary"
          class="absolute bottom-1.5 left-1.5 rounded-full bg-accent px-2 py-0.5 text-[10px] font-semibold text-accent-ink"
        >
          Обложка
        </span>
        <button
          v-else
          type="button"
          class="absolute bottom-1.5 left-1.5 inline-flex items-center gap-1 rounded-full bg-surface/90 px-2 py-0.5 text-[10px] font-semibold text-ink"
          @click="setCover(photo.id)"
        >
          <Star class="size-2.5" aria-hidden="true" />
          Сделать обложкой
        </button>
        <button
          type="button"
          class="absolute top-1.5 right-1.5 grid size-6 place-items-center rounded-full bg-bezel/60 text-white transition hover:bg-bezel/80"
          aria-label="Удалить фото"
          @click="removePhoto(photo.id)"
        >
          <X class="size-3.5" stroke-width="2.5" />
        </button>
      </div>

      <label
        v-if="photos.length < MAX_PHOTOS"
        class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border-2 border-hairline text-ink-soft transition-colors hover:border-accent hover:text-accent-text"
      >
        <Plus class="size-6" aria-hidden="true" />
        <span class="text-[11px] font-semibold">{{
          isUploadingPhoto ? 'Загрузка…' : 'Добавить'
        }}</span>
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

  <PaywallSheet
    :open="paywallOpen"
    :message="paywallMessage"
    @close="closePaywall"
    @upgrade="goToSubscriptionPlans"
  />
</template>
