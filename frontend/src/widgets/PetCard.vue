<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useCatalogStore } from '@/entities/catalog/model'
import { socialTagLabel } from '@/entities/pet/socialTags'
import type { Pet } from '@/entities/pet/types'
import ReportButton from '@/shared/ui/components/ReportButton.vue'

const props = defineProps<{ pet: Pet }>()
const catalogStore = useCatalogStore()

const speciesName = computed(() => catalogStore.speciesName(props.pet.species_id))
const breedName = computed(() =>
  props.pet.breed_id !== null
    ? catalogStore.breedName(props.pet.species_id, props.pet.breed_id)
    : null,
)

function ageLabel(birthdate: string | null): string | null {
  if (!birthdate) return null

  const ageInDays = (Date.now() - new Date(birthdate).getTime()) / (1000 * 60 * 60 * 24)
  const years = Math.floor(ageInDays / 365.25)

  if (years < 1) return 'до года'

  const lastDigit = years % 10
  const lastTwoDigits = years % 100
  let word = 'лет'
  if (lastTwoDigits < 11 || lastTwoDigits > 14) {
    if (lastDigit === 1) word = 'год'
    else if (lastDigit >= 2 && lastDigit <= 4) word = 'года'
  }

  return `${years} ${word}`
}

const age = computed(() => ageLabel(props.pet.birthdate))

async function loadBreed(): Promise<void> {
  await catalogStore.ensureBreedsLoaded(props.pet.species_id)
}

onMounted(loadBreed)
watch(() => props.pet.species_id, loadBreed)
</script>

<template>
  <div
    class="relative min-h-[420px] flex-1 overflow-hidden rounded-[20px]"
    style="background: linear-gradient(165deg, var(--teal-soft), var(--surface-soft))"
  >
    <img
      v-if="pet.photo_url"
      :src="pet.photo_url"
      class="absolute inset-0 h-full w-full object-cover"
      alt=""
    />
    <div class="absolute inset-x-0 bottom-0">
      <div
        class="p-4"
        style="
          background: linear-gradient(
            to top,
            color-mix(in srgb, var(--surface) 92%, transparent) 60%,
            transparent
          );
        "
      >
        <h3 class="text-xl font-semibold text-ink">{{ pet.name }}</h3>
        <p class="text-sm text-ink-soft">
          {{ speciesName }}<span v-if="breedName"> · {{ breedName }}</span
          ><span v-if="age"> · {{ age }}</span
          ><span v-if="pet.description"> · {{ pet.description }}</span>
        </p>
        <div v-if="pet.social_tags.length > 0" class="mt-1 flex flex-wrap gap-1">
          <span
            v-for="tag in pet.social_tags"
            :key="tag"
            class="rounded-full bg-surface/70 px-2 py-0.5 text-[11px] font-semibold text-ink-soft"
          >
            {{ socialTagLabel(tag) }}
          </span>
        </div>
        <ReportButton target-type="pet" :target-id="pet.id" />
      </div>
    </div>
  </div>
</template>
