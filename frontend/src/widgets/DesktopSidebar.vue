<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  Bell,
  ClipboardList,
  Gavel,
  Gem,
  Heart,
  House,
  Layers,
  MessageCircle,
  PawPrint,
  Receipt,
  Search,
  ShieldCheck,
  ShoppingBag,
  User,
} from 'lucide-vue-next'
import { useNotificationStore } from '@/entities/notification/model'
import { useUserStore } from '@/entities/user/model'
import { useStaff } from '@/shared/lib/useStaff'
import BaseAvatar from '@/shared/ui/components/BaseAvatar.vue'
import BaseCounter from '@/shared/ui/components/BaseCounter.vue'

/**
 * Боковая навигация по компоненту «Desktop / Sidebar» из макета. Показывается только
 * на широких экранах — на телефоне ту же роль играет нижняя панель, см. BottomNav.
 */
const route = useRoute()
const userStore = useUserStore()
const notificationStore = useNotificationStore()
const { isStaff } = useStaff()

const groups = computed(() => [
  {
    label: 'ОСНОВНОЕ',
    items: [
      { name: 'home', label: 'Лента', icon: Layers, section: '/' },
      { name: 'shelter-animals', label: 'Приюты', icon: House, section: '/shelters' },
      { name: 'my-adoption-requests', label: 'Заявки', icon: ClipboardList, section: '/adoption' },
      { name: 'marketplace', label: 'Маркет', icon: ShoppingBag, section: '/shop' },
    ],
  },
  {
    label: 'ОБЩЕНИЕ',
    items: [
      { name: 'conversations-list', label: 'Диалоги', icon: MessageCircle, section: '/chat' },
      { name: 'pending-likes', label: 'Лайки', icon: Heart, section: '/likes' },
      {
        name: 'notifications',
        label: 'Уведомления',
        icon: Bell,
        section: '/notifications',
        count: notificationStore.unreadCount,
      },
    ],
  },
  {
    label: 'МОЁ',
    items: [
      { name: 'my-orders', label: 'Заказы', icon: Receipt, section: '/orders' },
      { name: 'search-pets', label: 'Поиск', icon: Search, section: '/search' },
      { name: 'subscription-status', label: 'Тариф', icon: Gem, section: '/subscription' },
      { name: 'profile', label: 'Профиль', icon: User, section: '/profile' },
    ],
  },
  ...(isStaff.value
    ? [
        {
          label: 'МОДЕРАЦИЯ',
          items: [
            { name: 'admin-dashboard', label: 'Дашборд', icon: Gavel, section: '/admin' },
            {
              name: 'admin-shelter-verifications',
              label: 'Верификация',
              icon: ShieldCheck,
              section: '/admin/shelters',
            },
          ],
        },
      ]
    : []),
])

const isActive = (item: { name: string; section: string }): boolean =>
  route.name === item.name || (item.section !== '/' && route.path.startsWith(item.section))
</script>

<template>
  <aside
    class="sticky top-0 flex h-screen w-[264px] shrink-0 flex-col gap-4 overflow-y-auto border-r border-hairline bg-surface px-3.5 py-4"
  >
    <RouterLink :to="{ name: 'home' }" class="flex items-center gap-2 px-1">
      <span class="grid size-8 place-items-center rounded-[10px] bg-accent text-accent-ink">
        <PawPrint class="size-[18px]" aria-hidden="true" />
      </span>
      <span class="font-display text-lg font-bold text-ink">AnimalFriendly</span>
    </RouterLink>

    <nav class="flex flex-col gap-4">
      <div v-for="group in groups" :key="group.label" class="flex flex-col gap-1">
        <span class="px-2 text-[10px] font-bold tracking-wider text-ink-faint">{{
          group.label
        }}</span>
        <RouterLink
          v-for="item in group.items"
          :key="item.name"
          :to="{ name: item.name }"
          class="flex h-10 items-center gap-2.5 rounded-xl px-2.5 text-[13.5px] transition-colors"
          :class="
            isActive(item)
              ? 'bg-accent-soft font-bold text-accent-text'
              : 'font-medium text-ink hover:bg-surface-soft'
          "
        >
          <component :is="item.icon" class="size-[18px] shrink-0" aria-hidden="true" />
          <span class="flex-1 truncate">{{ item.label }}</span>
          <BaseCounter v-if="item.count" :value="item.count" :limit="99" />
        </RouterLink>
      </div>
    </nav>

    <RouterLink
      :to="{ name: 'profile' }"
      class="mt-auto flex items-center gap-2.5 rounded-xl border-t border-hairline px-1 pt-4"
    >
      <BaseAvatar :src="userStore.currentUser?.avatar_url" :name="userStore.currentUser?.name ?? undefined" size="sm" />
      <span class="flex min-w-0 flex-1 flex-col">
        <span class="truncate text-[13px] font-bold text-ink">{{
          userStore.currentUser?.name ?? 'Профиль'
        }}</span>
        <span class="truncate text-[11px] text-ink-faint">{{ userStore.currentUser?.phone }}</span>
      </span>
    </RouterLink>
  </aside>
</template>
