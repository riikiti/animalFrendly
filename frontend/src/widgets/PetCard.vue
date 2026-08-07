<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useCatalogStore } from '@/entities/catalog/model'
import { socialTagLabel } from '@/entities/pet/socialTags'
import type { Pet } from '@/entities/pet/types'
import BaseChip from '@/shared/ui/components/BaseChip.vue'
import ReportButton from '@/shared/ui/components/ReportButton.vue'

const props = defineProps<{ pet: Pet }>()
const emit = defineEmits<{ swipe: [action: 'like' | 'dislike'] }>()
const catalogStore = useCatalogStore()

const SWIPE_THRESHOLD = 100

const isDragging = ref(false)
const dragX = ref(0)
const dragY = ref(0)
const startX = ref(0)
const startY = ref(0)

function onPointerDown(event: PointerEvent): void {
  if ((event.target as HTMLElement).closest('button')) return

  isDragging.value = true
  startX.value = event.clientX
  startY.value = event.clientY
  ;(event.currentTarget as HTMLElement).setPointerCapture(event.pointerId)
}

function onPointerMove(event: PointerEvent): void {
  if (!isDragging.value) return
  dragX.value = event.clientX - startX.value
  dragY.value = event.clientY - startY.value
}

function onPointerUp(): void {
  if (!isDragging.value) return
  isDragging.value = false

  if (Math.abs(dragX.value) > SWIPE_THRESHOLD) {
    emit('swipe', dragX.value > 0 ? 'like' : 'dislike')
  }

  dragX.value = 0
  dragY.value = 0
}

const cardStyle = computed(() => ({
  transform: `translate(${dragX.value}px, ${dragY.value}px) rotate(${dragX.value / 20}deg)`,
  transition: isDragging.value ? 'none' : 'transform 0.3s ease',
  touchAction: 'none',
}))

const likeOpacity = computed(() => Math.min(Math.max(dragX.value / SWIPE_THRESHOLD, 0), 1))
const nopeOpacity = computed(() => Math.min(Math.max(-dragX.value / SWIPE_THRESHOLD, 0), 1))

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
    class="relative min-h-[420px] flex-1 select-none overflow-hidden rounded-card shadow-md"
    :style="[
      { background: 'linear-gradient(165deg, var(--accent-soft), var(--surface-soft))' },
      cardStyle,
    ]"
    @pointerdown="onPointerDown"
    @pointermove="onPointerMove"
    @pointerup="onPointerUp"
    @pointercancel="onPointerUp"
  >
    <div
      class="pointer-events-none absolute top-4 left-4 z-10 -rotate-12 rounded-xl border-4 border-teal px-3 py-1 font-display text-xl font-bold text-teal uppercase"
      :style="{ opacity: likeOpacity }"
    >
      Like
    </div>
    <div
      class="pointer-events-none absolute top-4 right-4 z-10 rotate-12 rounded-xl border-4 border-danger px-3 py-1 font-display text-xl font-bold text-danger uppercase"
      :style="{ opacity: nopeOpacity }"
    >
      Nope
    </div>

    <img
      v-if="pet.photo_url"
      :src="pet.photo_url"
      class="pointer-events-none absolute inset-0 h-full w-full object-cover"
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
        <h3 class="font-display text-2xl font-bold text-ink">{{ pet.name }}</h3>
        <p class="mt-0.5 text-sm text-ink-soft">
          {{ speciesName }}<span v-if="breedName"> · {{ breedName }}</span
          ><span v-if="age"> · {{ age }}</span
          ><span v-if="pet.description"> · {{ pet.description }}</span>
        </p>
        <div v-if="pet.social_tags.length > 0" class="mt-2 flex flex-wrap gap-1.5">
          <BaseChip v-for="tag in pet.social_tags" :key="tag" tone="soft">
            {{ socialTagLabel(tag) }}
          </BaseChip>
        </div>
        <ReportButton target-type="pet" :target-id="pet.id" />
      </div>
    </div>
  </div>
</template>
