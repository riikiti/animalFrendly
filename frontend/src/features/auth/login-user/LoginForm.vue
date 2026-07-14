<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'

const router = useRouter()
const userStore = useUserStore()

const form = reactive({
  phone: '',
  password: '',
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
  <form class="flex flex-col gap-4" @submit.prevent="onSubmit">
    <BaseInput v-model="form.phone" label="Номер телефона" placeholder="+7 926 123-45-67" />
    <BaseInput v-model="form.password" type="password" label="Пароль" />

    <p v-if="generalError" class="text-xs text-danger">{{ generalError }}</p>

    <BaseButton type="submit" :disabled="isSubmitting">
      {{ isSubmitting ? 'Входим…' : 'Войти' }}
    </BaseButton>
  </form>
</template>
