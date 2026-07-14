<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()

const tabs = [
  { name: 'home', label: 'Лента' },
  { name: 'shelter-animals', label: 'Приюты' },
  { name: 'my-adoption-requests', label: 'Заявки' },
  { name: 'marketplace', label: 'Маркет' },
] as const
</script>

<template>
  <nav class="flex justify-around border-t border-hairline bg-surface pb-2 pt-2">
    <RouterLink
      v-for="tab in tabs"
      :key="tab.name"
      :to="{ name: tab.name }"
      class="px-3 py-1 text-xs font-semibold"
      :class="route.name === tab.name ? 'text-teal' : 'text-ink-faint'"
    >
      {{ tab.label }}
    </RouterLink>
  </nav>
</template>
