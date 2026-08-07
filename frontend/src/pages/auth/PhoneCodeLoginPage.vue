<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ChevronLeft } from 'lucide-vue-next'
import PhoneCodeForm from '@/features/auth/phone-code/PhoneCodeForm.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const userStore = useUserStore()

const busy = ref(false)
const error = ref('')

const submit = async ({ phone, code }: { phone: string; code: string }) => {
  error.value = ''
  busy.value = true

  try {
    await userStore.loginWithPhoneCode({ phone, code, remember: true })
    await router.push({ name: 'home' })
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 pt-6 pb-8 lg:max-w-lg">
    <RouterLink
      :to="{ name: 'login' }"
      class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft transition-colors hover:bg-surface-soft"
      aria-label="Назад"
    >
      <ChevronLeft class="size-5" />
    </RouterLink>

    <div class="mt-6 flex flex-col gap-1.5">
      <h1 class="font-display text-[28px] leading-tight font-bold text-ink">Вход по коду</h1>
      <p class="text-sm text-ink-soft">Пришлём короткий код на ваш номер — пароль не понадобится</p>
    </div>

    <PhoneCodeForm
      class="mt-6"
      purpose="login"
      submit-label="Войти"
      :busy="busy"
      :error="error"
      @submit="submit"
    />

    <p class="mt-auto pt-8 text-center text-[13.5px] text-ink-soft">
      Помните пароль?
      <RouterLink :to="{ name: 'login' }" class="font-bold text-accent-text">Войти как обычно</RouterLink>
    </p>
  </div>
</template>
