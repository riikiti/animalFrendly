<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCatalogStore } from '@/entities/catalog/model'
import * as searchApi from '@/entities/search/api'
import type { PetSearchResult } from '@/entities/search/types'
import BaseInput from '@/shared/ui/components/BaseInput.vue'

const router = useRouter()
const catalogStore = useCatalogStore()

const query = ref('')
const speciesId = ref<number | null>(null)
const sex = ref<'male' | 'female' | null>(null)
const purpose = ref<'social' | 'breeding' | 'shelter' | null>(null)
const city = ref('')
const isVaccinated = ref(false)

const results = ref<PetSearchResult[]>([])
const isLoading = ref(true)

const purposeLabels: Record<string, string> = {
  social: 'Общение',
  breeding: 'Вязка',
  shelter: 'Приют',
}

async function search(): Promise<void> {
  isLoading.value = true
  const response = await searchApi.searchPets({
    q: query.value.trim() || undefined,
    species_id: speciesId.value ?? undefined,
    sex: sex.value ?? undefined,
    purpose: purpose.value ?? undefined,
    city: city.value.trim() || undefined,
    is_vaccinated: isVaccinated.value || undefined,
    per_page: 30,
  })
  results.value = response.data
  isLoading.value = false
}

onMounted(async () => {
  await catalogStore.ensureSpeciesLoaded()
  await search()
})

function toggleSpecies(id: number): void {
  speciesId.value = speciesId.value === id ? null : id
  search()
}

function toggleSex(value: 'male' | 'female'): void {
  sex.value = sex.value === value ? null : value
  search()
}

function togglePurpose(value: 'social' | 'breeding' | 'shelter'): void {
  purpose.value = purpose.value === value ? null : value
  search()
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Поиск питомцев</span>
      <button class="text-lg text-ink-faint" aria-label="Закрыть" @click="router.back()">✕</button>
    </div>

    <div class="flex flex-col gap-3 px-2">
      <BaseInput v-model="query" label="Кличка" placeholder="Например, Рекс" @change="search" />

      <div class="flex flex-wrap gap-2">
        <button
          v-for="s in catalogStore.species"
          :key="s.id"
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="speciesId === s.id ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="toggleSpecies(s.id)"
        >
          {{ s.name }}
        </button>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="sex === 'male' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="toggleSex('male')"
        >
          Мальчик
        </button>
        <button
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="sex === 'female' ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="toggleSex('female')"
        >
          Девочка
        </button>
        <button
          v-for="p in ['social', 'breeding', 'shelter'] as const"
          :key="p"
          type="button"
          class="rounded-full px-3.5 py-1.5 text-sm font-semibold"
          :class="purpose === p ? 'bg-teal text-white' : 'bg-surface-soft text-ink-soft'"
          @click="togglePurpose(p)"
        >
          {{ purposeLabels[p] }}
        </button>
      </div>

      <BaseInput v-model="city" label="Город" placeholder="Например, Москва" @change="search" />

      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="isVaccinated" type="checkbox" class="h-4 w-4" @change="search" />
        Только вакцинированные
      </label>
    </div>

    <div v-if="!isLoading" class="flex flex-col gap-2 px-2 pb-4">
      <p
        v-if="results.length === 0"
        class="rounded-2xl bg-surface-soft p-4 text-center text-sm text-ink-faint"
      >
        Ничего не найдено — попробуйте другие фильтры
      </p>

      <div
        v-for="pet in results"
        :key="pet.id"
        class="flex items-center gap-3 rounded-2xl border border-hairline p-3"
      >
        <img
          v-if="pet.photo_url"
          :src="pet.photo_url"
          class="h-13 w-13 shrink-0 rounded-xl object-cover"
          alt=""
        />
        <div
          v-else
          class="flex h-13 w-13 shrink-0 items-center justify-center rounded-xl bg-teal-soft text-lg font-semibold text-teal"
        >
          {{ pet.name.charAt(0) }}
        </div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-ink">{{ pet.name }}</p>
          <p class="text-xs text-ink-faint">
            {{ pet.species_name }}<span v-if="pet.breed_name"> · {{ pet.breed_name }}</span>
            <span v-if="pet.city"> · {{ pet.city }}</span>
          </p>
        </div>
        <span
          v-if="pet.distance_km !== null"
          class="shrink-0 rounded-full bg-surface-soft px-2 py-1 text-xs font-semibold text-ink-soft"
        >
          {{ pet.distance_km }} км
        </span>
      </div>
    </div>
  </div>
</template>
