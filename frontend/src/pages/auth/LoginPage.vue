<script setup lang="ts">
import { RouterLink } from 'vue-router'
import LoginForm from '@/features/auth/login-user/LoginForm.vue'
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col justify-center gap-6 px-6 py-10 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex flex-col items-center gap-1 text-center">
      <span class="font-display text-2xl text-ink">AnimalFriendly</span>
      <p class="text-sm text-ink-soft">С возвращением</p>
    </div>
    <LoginForm />
    <p class="text-center text-xs text-ink-faint">
      Ещё нет аккаунта?
      <RouterLink :to="{ name: 'register' }" class="font-semibold text-teal"
        >Зарегистрироваться</RouterLink
      >
    </p>
  </div>
</template>
