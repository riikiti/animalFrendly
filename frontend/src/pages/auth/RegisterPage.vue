<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { PawPrint } from 'lucide-vue-next'
import RegisterForm from '@/features/auth/register-user/RegisterForm.vue'
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 pt-8 pb-8 lg:max-w-lg">
    <span class="inline-flex items-center gap-2 self-start">
      <span class="grid size-7 place-items-center rounded-[10px] bg-accent text-accent-ink">
        <PawPrint class="size-4" aria-hidden="true" />
      </span>
      <span class="font-display text-base font-bold text-ink">AnimalFriendly</span>
    </span>

    <div class="mt-7 flex flex-col gap-1.5">
      <h1 class="font-display text-[28px] leading-tight font-bold text-ink">Создайте аккаунт</h1>
      <p class="text-sm text-ink-soft">
        Пара, друг для прогулок или новый дом — всё в одном приложении
      </p>
    </div>

    <RegisterForm class="mt-6" />

    <p class="mt-auto pt-8 text-center text-[13.5px] text-ink-soft">
      Уже есть аккаунт?
      <RouterLink :to="{ name: 'login' }" class="font-bold text-accent-text">Войти</RouterLink>
    </p>
  </div>
</template>
