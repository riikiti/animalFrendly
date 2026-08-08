<script setup lang="ts">
import { useRoute } from 'vue-router'
import { ClipboardList, House, Layers, ShoppingBag, User } from 'lucide-vue-next'

const route = useRoute()

const tabs = [
  { name: 'home', label: 'Лента', icon: Layers, section: '/' },
  { name: 'shelter-animals', label: 'Приюты', icon: House, section: '/shelters' },
  { name: 'my-adoption-requests', label: 'Заявки', icon: ClipboardList, section: '/adoption' },
  // Магазин товаров живёт внутри «Маркета» — вкладка остаётся активной и на /shop.
  { name: 'marketplace', label: 'Маркет', icon: ShoppingBag, section: '/shop' },
  { name: 'profile', label: 'Профиль', icon: User, section: '/profile' },
] as const

const isActive = (tab: (typeof tabs)[number]): boolean =>
  route.name === tab.name || (tab.section !== '/' && route.path.startsWith(tab.section))
</script>

<template>
  <!-- Плавающая таблетка по компоненту Tab Bar из макета. sticky, а не fixed: панель
  остаётся в потоке, поэтому под ней не приходится резервировать отступ на каждом экране.
  flex-1 на вкладке гарантирует, что ряд из пяти пунктов укладывается в ширину даже на
  экранах 320px — раньше «Профиль» уезжал за границу вьюпорта. -->
  <nav class="sticky bottom-0 z-30 px-4 pt-2 pb-3">
    <div
      class="flex gap-0.5 rounded-[28px] border border-hairline p-1.5 shadow-nav backdrop-blur-[20px]"
      style="background: var(--nav-veil)"
    >
      <RouterLink
        v-for="tab in tabs"
        :key="tab.name"
        :to="{ name: tab.name }"
        class="flex h-11 flex-1 flex-col items-center justify-center gap-0.5 rounded-[20px] px-1 text-center text-[10px] transition-colors"
        :class="
          isActive(tab)
            ? 'bg-accent-soft font-semibold text-accent-text'
            : 'font-medium text-ink-faint'
        "
      >
        <component :is="tab.icon" class="size-[22px]" aria-hidden="true" />
        {{ tab.label }}
      </RouterLink>
    </div>
  </nav>
</template>
