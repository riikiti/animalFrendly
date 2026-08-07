<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseCheckbox from '@/shared/ui/components/BaseCheckbox.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const userStore = useUserStore()

const form = reactive({
  phone: '',
  password: '',
  // Без галочки токен живёт сутки, с ней — 90 дней (см. AuthController::issueToken).
  remember: false,
})

const isSubmitting = ref(false)
const generalError = ref('')

async function onSubmit(): Promise<void> {
  generalError.value = ''
  isSubmitting.value = true

  try {
    await userStore.login(form)
    await router.push({ name: 'home' })
  } catch (error) {
    generalError.value =
      error instanceof ApiError ? error.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <form class="flex flex-col gap-3.5" @submit.prevent="onSubmit">
    <BaseInput
      v-model="form.phone"
      label="Номер телефона"
      type="tel"
      inputmode="tel"
      placeholder="+7 926 123-45-67"
    />
    <BaseInput v-model="form.password" type="password" label="Пароль" />

    <div class="flex items-center justify-between gap-3 pt-0.5">
      <BaseCheckbox v-model="form.remember">Запомнить меня</BaseCheckbox>
      <RouterLink
        :to="{ name: 'password-reset' }"
        class="shrink-0 text-[13px] font-bold text-accent-text"
        >Забыли пароль?</RouterLink
      >
    </div>

    <BaseAlert v-if="generalError" tone="error">{{ generalError }}</BaseAlert>

    <BaseButton type="submit" size="lg" block class="mt-1" :loading="isSubmitting">
      {{ isSubmitting ? 'Входим…' : 'Войти' }}
    </BaseButton>
  </form>
</template>
