<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BaseCheckbox from '@/shared/ui/components/BaseCheckbox.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const userStore = useUserStore()

const form = reactive({
  phone: '',
  password: '',
  password_confirmation: '',
  personal_data_consent: false,
})

const isSubmitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

async function onSubmit(): Promise<void> {
  errors.value = {}
  generalError.value = ''
  isSubmitting.value = true

  try {
    await userStore.register(form)
    await router.push({ name: 'onboarding-mode' })
  } catch (error) {
    if (error instanceof ApiError) {
      errors.value = error.errors ?? {}
      if (!error.errors) generalError.value = error.message
    } else {
      generalError.value = 'Что-то пошло не так. Попробуйте ещё раз.'
    }
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
      :error="errors.phone?.[0]"
    />
    <BaseInput
      v-model="form.password"
      type="password"
      label="Пароль"
      :error="errors.password?.[0]"
    />
    <BaseInput v-model="form.password_confirmation" type="password" label="Повторите пароль" />

    <div class="flex flex-col gap-1">
      <BaseCheckbox v-model="form.personal_data_consent">
        Даю согласие на обработку персональных данных в соответствии с 152-ФЗ и принимаю условия
        пользовательского соглашения
      </BaseCheckbox>
      <p v-if="errors.personal_data_consent" class="text-xs text-danger">
        {{ errors.personal_data_consent[0] }}
      </p>
    </div>

    <BaseAlert v-if="generalError" tone="error">{{ generalError }}</BaseAlert>

    <BaseButton type="submit" size="lg" block class="mt-1" :loading="isSubmitting">
      {{ isSubmitting ? 'Создаём анкету…' : 'Продолжить' }}
    </BaseButton>
  </form>
</template>
