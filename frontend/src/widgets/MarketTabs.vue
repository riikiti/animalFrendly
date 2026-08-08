<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import BaseSegmented from '@/shared/ui/components/BaseSegmented.vue'

/**
 * Переключатель разделов «Маркета»: питомцы от заводчиков и товары для животных.
 * Отдельной вкладки в нижней навигации у магазина нет — оба раздела про покупки,
 * и панель из пяти пунктов не разрастается.
 */
const route = useRoute()
const router = useRouter()

const go = (value: string | number) => {
  router.replace({ name: value === 'shop' ? 'shop' : 'marketplace' })
}
</script>

<template>
  <BaseSegmented
    class="lg:max-w-sm"
    :model-value="route.name === 'shop' ? 'shop' : 'pets'"
    aria-label="Раздел маркета"
    :options="[
      { value: 'pets', label: 'Питомцы' },
      { value: 'shop', label: 'Товары' },
    ]"
    @update:model-value="go"
  />
</template>
