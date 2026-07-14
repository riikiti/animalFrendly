<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BaseCheckbox from '@/shared/ui/components/BaseCheckbox.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const userStore = useUserStore()

const roles = [
  { value: 'owner', title: 'Я — владелец', description: 'Знакомства, приюты, покупки' },
  { value: 'breeder', title: 'Я — заводчик', description: 'Продажа щенков/котят' },
  { value: 'shelter', title: 'Я — приют', description: 'Пристройство животных' },
] as const

const form = reactive({
  phone: '',
  password: '',
  password_confirmation: '',
  account_type: 'owner' as (typeof roles)[number]['value'],
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
    await router.push({ name: 'home' })
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
  <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
    <div class="flex flex-col gap-2">
      <span class="text-xs font-semibold text-ink-soft">Кто вы?</span>
      <button
        v-for="role in roles"
        :key="role.value"
        type="button"
        class="flex items-center gap-3 rounded-2xl border px-3.5 py-3 text-left transition"
        :class="
          form.account_type === role.value
            ? 'border-teal bg-teal-soft'
            : 'border-hairline hover:bg-surface-soft'
        "
        @click="form.account_type = role.value"
      >
        <span>
          <span class="block text-sm font-semibold text-ink">{{ role.title }}</span>
          <span class="block text-xs text-ink-faint">{{ role.description }}</span>
        </span>
      </button>
      <p v-if="errors.account_type" class="text-xs text-danger">{{ errors.account_type[0] }}</p>
    </div>

    <BaseInput
      v-model="form.phone"
      label="Номер телефона"
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

    <p v-if="generalError" class="text-xs text-danger">{{ generalError }}</p>

    <BaseButton type="submit" :disabled="isSubmitting">
      {{ isSubmitting ? 'Создаём анкету…' : 'Продолжить' }}
    </BaseButton>
  </form>
</template>
