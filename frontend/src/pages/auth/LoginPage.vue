<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { PawPrint } from 'lucide-vue-next'
import LoginForm from '@/features/auth/login-user/LoginForm.vue'
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 pt-6 pb-8 lg:max-w-lg">
    <!-- Приветственный блок вместо фотографии из макета: своих изображений в проекте нет,
    поэтому бренд показываем на мягкой коралловой подложке. -->
    <div class="flex h-[172px] shrink-0 items-center justify-center rounded-3xl bg-accent-soft">
      <span
        class="inline-flex items-center gap-2 rounded-full bg-surface/90 py-1.5 pr-3.5 pl-2 shadow-sm"
      >
        <span class="grid size-6 place-items-center rounded-lg bg-accent text-accent-ink">
          <PawPrint class="size-3.5" aria-hidden="true" />
        </span>
        <span class="font-display text-[15px] font-bold text-ink">AnimalFriendly</span>
      </span>
    </div>

    <div class="mt-6 flex flex-col gap-1.5">
      <h1 class="font-display text-[28px] leading-tight font-bold text-ink">С возвращением</h1>
      <p class="text-sm text-ink-soft">Питомцы рядом уже ждут новых знакомств</p>
    </div>

    <LoginForm class="mt-6" />

    <p class="mt-auto pt-8 text-center text-[13.5px] text-ink-soft">
      Нет аккаунта?
      <RouterLink :to="{ name: 'register' }" class="font-bold text-accent-text"
        >Зарегистрироваться</RouterLink
      >
    </p>
  </div>
</template>
