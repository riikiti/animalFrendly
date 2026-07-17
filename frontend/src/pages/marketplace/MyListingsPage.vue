<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import * as catalogApi from '@/entities/catalog/api'
import { useCatalogStore } from '@/entities/catalog/model'
import type { Breed } from '@/entities/catalog/types'
import * as marketplaceApi from '@/entities/marketplace/api'
import type { Listing } from '@/entities/marketplace/types'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const catalogStore = useCatalogStore()

const listings = ref<Listing[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const showForm = ref(false)

const form = reactive({
  speciesId: null as number | null,
  breedId: null as number | null,
  name: '',
  sex: 'male' as 'male' | 'female',
  priceRub: '',
  parentPetId: null as string | null,
})

const breeds = ref<Breed[]>([])
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

let isMounted = true
onUnmounted(() => {
  isMounted = false
})

onMounted(async () => {
  try {
    await catalogStore.ensureSpeciesLoaded()
    const first = catalogStore.species[0]
    if (first) await selectSpecies(first.slug, first.id)

    const response = await marketplaceApi.listMyListings()
    listings.value = response.data
  } catch (error) {
    if (isMounted) throw error
  } finally {
    if (isMounted) isLoading.value = false
  }
})

async function selectSpecies(slug: string, id: number): Promise<void> {
  form.speciesId = id
  form.breedId = null
  const response = await catalogApi.listBreeds(slug)
  breeds.value = response.data
}

async function refresh(): Promise<void> {
  const response = await marketplaceApi.listMyListings()
  listings.value = response.data
}

async function onSubmit(): Promise<void> {
  if (form.speciesId === null) return

  errors.value = {}
  generalError.value = ''
  isCreating.value = true

  try {
    await marketplaceApi.createListing({
      species_id: form.speciesId,
      breed_id: form.breedId,
      name: form.name,
      sex: form.sex,
      price_amount: Math.round(parseFloat(form.priceRub.replace(',', '.')) * 100),
      parent_pet_id: form.parentPetId,
    })
    form.name = ''
    form.priceRub = ''
    form.parentPetId = null
    showForm.value = false
    await refresh()
  } catch (error) {
    if (error instanceof ApiError) {
      errors.value = error.errors ?? {}
      if (!error.errors) generalError.value = error.message
    } else {
      generalError.value = 'Что-то пошло не так. Попробуйте ещё раз.'
    }
  } finally {
    isCreating.value = false
  }
}

async function publish(listingId: string): Promise<void> {
  await marketplaceApi.publishListing(listingId)
  await refresh()
}

async function archive(listingId: string): Promise<void> {
  await marketplaceApi.archiveListing(listingId)
  await refresh()
}

const statusLabels: Record<string, string> = {
  draft: 'Черновик',
  published: 'Опубликован',
  reserved: 'Забронирован',
  sold: 'Продан',
  archived: 'В архиве',
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Мои листинги</span>
      <button class="text-xs font-semibold text-teal" @click="router.push({ name: 'marketplace' })">
        В маркет
      </button>
    </div>

    <div v-if="!isLoading" class="flex flex-1 flex-col gap-3 pb-4">
      <BaseButton v-if="!showForm" variant="outline" @click="showForm = true">
        Выставить питомца на продажу
      </BaseButton>

      <form
        v-if="showForm"
        class="flex flex-col gap-3 rounded-2xl border border-hairline p-3"
        @submit.prevent="onSubmit"
      >
        <BaseInput
          v-model="form.name"
          label="Кличка"
          placeholder="Рекс"
          :error="errors.name?.[0]"
        />

        <div class="flex flex-col gap-2">
          <span class="text-xs font-semibold text-ink-soft">Вид</span>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="s in catalogStore.species"
              :key="s.id"
              type="button"
              class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
              :class="
                form.speciesId === s.id ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'
              "
              @click="selectSpecies(s.slug, s.id)"
            >
              {{ s.name }}
            </button>
          </div>
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
              :class="
                form.sex === 'female' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'
              "
              @click="form.sex = 'female'"
            >
              Девочка
            </button>
          </div>
        </div>

        <BaseInput
          v-model="form.priceRub"
          label="Цена, ₽"
          placeholder="15000"
          :error="errors.price_amount?.[0]"
        />

        <div v-if="listings.length > 0" class="flex flex-col gap-2">
          <span class="text-xs font-semibold text-ink-soft">Родитель (если есть)</span>
          <select
            v-model="form.parentPetId"
            aria-label="Родитель"
            class="rounded-xl bg-surface-soft px-3.5 py-2.5 text-sm text-ink outline-none"
          >
            <option :value="null">Не указан</option>
            <option v-for="l in listings" :key="l.pet_id" :value="l.pet_id">
              {{ l.pet?.name ?? 'Питомец' }}
            </option>
          </select>
        </div>

        <p v-if="generalError" class="text-xs text-danger">{{ generalError }}</p>

        <div class="flex gap-2">
          <BaseButton type="submit" :disabled="isCreating || form.speciesId === null">
            {{ isCreating ? 'Создаём…' : 'Создать' }}
          </BaseButton>
          <BaseButton variant="ghost" type="button" @click="showForm = false">Отмена</BaseButton>
        </div>
      </form>

      <p
        v-if="listings.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        У вас пока нет листингов
      </p>

      <div
        v-for="listing in listings"
        :key="listing.id"
        class="flex flex-col gap-2 rounded-2xl border border-hairline p-3"
      >
        <div class="flex items-center gap-3">
          <img
            v-if="listing.pet?.photo_url"
            :src="listing.pet.photo_url"
            class="h-11 w-11 shrink-0 rounded-xl object-cover"
            alt=""
          />
          <div
            v-else
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-soft text-sm font-semibold text-teal"
          >
            {{ (listing.pet?.name ?? '?').charAt(0) }}
          </div>
          <div class="flex flex-1 items-center justify-between">
            <p class="text-sm font-semibold text-ink">{{ listing.pet?.name ?? 'Питомец' }}</p>
            <span
              class="rounded-full bg-surface-soft px-2 py-1 text-xs font-semibold text-ink-soft"
            >
              {{ statusLabels[listing.status] }}
            </span>
          </div>
        </div>
        <p v-if="listing.pet?.parent_name" class="text-xs text-ink-faint">
          Родитель: {{ listing.pet.parent_name }}
        </p>

        <BaseButton
          v-if="listing.status === 'draft'"
          variant="outline"
          @click="publish(listing.id)"
        >
          Опубликовать
        </BaseButton>
        <BaseButton
          v-if="listing.status === 'draft' || listing.status === 'published'"
          variant="ghost"
          @click="archive(listing.id)"
        >
          В архив
        </BaseButton>
      </div>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
