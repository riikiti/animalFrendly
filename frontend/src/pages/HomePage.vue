<script setup lang="ts">
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import { useUserStore } from '@/entities/user/model'

const userStore = useUserStore()
const router = useRouter()

async function onLogout(): Promise<void> {
  await userStore.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-6 px-6 py-10">
    <div class="flex items-center justify-between">
      <span class="font-display text-xl text-ink">AnimalFriendly</span>
      <BaseButton variant="ghost" @click="onLogout">Выйти</BaseButton>
    </div>
    <div class="rounded-2xl bg-surface-soft p-5">
      <p class="text-sm text-ink-soft">Вы вошли как</p>
      <p class="text-base font-semibold text-ink">{{ userStore.currentUser?.phone }}</p>
    </div>
    <p class="text-sm text-ink-faint">
      Лента мэтчинга появится в следующей фазе разработки — см. docs/plan/00-overview.md.
    </p>
  </div>
</template>
