<script setup lang="ts">
import { computed } from 'vue'
import { useCatalogStore } from '@/entities/catalog/model'
import type { Pet } from '@/entities/pet/types'
import ReportButton from '@/shared/ui/components/ReportButton.vue'

const props = defineProps<{ pet: Pet }>()
const catalogStore = useCatalogStore()

const speciesName = computed(() => catalogStore.speciesName(props.pet.species_id))
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
          {{ speciesName }}<span v-if="pet.description"> · {{ pet.description }}</span>
        </p>
        <ReportButton target-type="pet" :target-id="pet.id" />
      </div>
    </div>
  </div>
</template>
