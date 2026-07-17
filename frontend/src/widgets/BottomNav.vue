<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()

const tabs = [
  { name: 'home', label: 'Лента' },
  { name: 'shelter-animals', label: 'Приюты' },
  { name: 'my-adoption-requests', label: 'Заявки' },
  { name: 'marketplace', label: 'Маркет' },
  { name: 'profile', label: 'Профиль' },
] as const
</script>

<template>
  <!-- flex-1 на каждой вкладке гарантирует, что ряд из пяти пунктов всегда укладывается в
  ширину контейнера — на узких экранах (320-360px) с justify-around и content-sized
  ссылками последний пункт («Профиль») обрезался за пределами вьюпорта без прокрутки. -->
  <nav class="flex border-t border-hairline bg-surface pb-2 pt-2">
    <RouterLink
      v-for="tab in tabs"
      :key="tab.name"
      :to="{ name: tab.name }"
      class="flex-1 px-1 py-1 text-center text-xs font-semibold"
      :class="route.name === tab.name ? 'text-teal' : 'text-ink-faint'"
    >
      {{ tab.label }}
    </RouterLink>
  </nav>
</template>
