<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import DesktopSidebar from './DesktopSidebar.vue'

/**
 * Десктопная оболочка: боковая навигация слева, содержимое справа.
 *
 * Включается только от lg и только на экранах под авторизацией — вход, регистрация и
 * восстановление пароля остаются одной колонкой по центру, как и на телефоне.
 * Сайдбар прячется классом, а не v-if: так при изменении ширины окна страница не
 * перемонтируется и не теряет своё состояние.
 */
const route = useRoute()

const withSidebar = computed(() => route.meta.requiresAuth === true)
</script>

<template>
  <div :class="withSidebar ? 'lg:flex lg:min-h-screen lg:items-stretch' : ''">
    <DesktopSidebar v-if="withSidebar" class="hidden lg:flex" />
    <div :class="withSidebar ? 'min-w-0 flex-1' : ''"><slot /></div>
  </div>
</template>
