<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ChevronLeft } from 'lucide-vue-next'
import PhoneCodeForm from '@/features/auth/phone-code/PhoneCodeForm.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'

const router = useRouter()
const userStore = useUserStore()

// Код проверяем не отдельным запросом, а вместе с новым паролем — так он гасится ровно
// один раз и не остаётся «подтверждённым» висеть между шагами.
const verified = ref<{ phone: string; code: string } | null>(null)
const password = ref('')
const passwordConfirmation = ref('')
const busy = ref(false)
const error = ref('')

const submitNewPassword = async () => {
  if (!verified.value) return
  error.value = ''
  busy.value = true

  try {
    await userStore.resetPassword({
      phone: verified.value.phone,
      code: verified.value.code,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
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
      <h1 class="font-display text-[28px] leading-tight font-bold text-ink">Новый пароль</h1>
      <p class="text-sm text-ink-soft">Подтвердите номер кодом из СМС и придумайте новый пароль</p>
    </div>

    <PhoneCodeForm
      v-if="!verified"
      class="mt-6"
      purpose="password_reset"
      submit-label="Подтвердить номер"
      @submit="verified = $event"
    />

    <form v-else class="mt-6 flex flex-col gap-3.5" @submit.prevent="submitNewPassword">
      <BaseInput v-model="password" type="password" label="Новый пароль" />
      <BaseInput v-model="passwordConfirmation" type="password" label="Повторите пароль" />

      <BaseAlert v-if="error" tone="error">{{ error }}</BaseAlert>

      <BaseButton
        type="submit"
        size="lg"
        block
        class="mt-1"
        :loading="busy"
        :disabled="password.length < 8"
      >
        Сохранить пароль
      </BaseButton>
    </form>

    <p class="mt-auto pt-8 text-center text-[13.5px] text-ink-soft">
      Вспомнили пароль?
      <RouterLink :to="{ name: 'login' }" class="font-bold text-accent-text">Войти</RouterLink>
    </p>
  </div>
</template>
