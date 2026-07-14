<script setup lang="ts">
import { RouterLink } from 'vue-router'
import RegisterForm from '@/features/auth/register-user/RegisterForm.vue'
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col justify-center gap-6 px-6 py-10">
    <div class="flex flex-col items-center gap-1 text-center">
      <span class="font-display text-2xl text-ink">AnimalFriendly</span>
      <p class="text-sm text-ink-soft">Находите друзей, дом и потомство — для питомцев рядом</p>
    </div>
    <RegisterForm />
    <p class="text-center text-xs text-ink-faint">
      Уже есть аккаунт?
      <RouterLink :to="{ name: 'login' }" class="font-semibold text-teal">Войти</RouterLink>
    </p>
  </div>
</template>
