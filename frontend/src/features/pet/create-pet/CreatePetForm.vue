<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import * as catalogApi from '@/entities/catalog/api'
import { useCatalogStore } from '@/entities/catalog/model'
import type { Breed } from '@/entities/catalog/types'
import { usePetStore } from '@/entities/pet/model'
import { ApiError } from '@/shared/api/http'

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

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()

  const first = catalogStore.species[0]
  if (first) {
    await selectSpecies(first.slug, first.id)
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
    await petStore.createPet({
      species_id: form.speciesId,
      breed_id: form.breedId,
      name: form.name,
      sex: form.sex,
      purpose: form.purpose,
    })
    await router.push({ name: 'home' })
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
</script>

<template>
  <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
    <div
      class="flex h-32 flex-col items-center justify-center gap-2 rounded-2xl bg-teal-soft text-teal"
    >
      <span class="text-xs">Фото добавим позже</span>
    </div>

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
</template>
