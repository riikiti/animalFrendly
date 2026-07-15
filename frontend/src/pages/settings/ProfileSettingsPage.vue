<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'

const router = useRouter()
const userStore = useUserStore()

const address = ref('')
const isSaving = ref(false)
const error = ref('')
const saved = ref(false)

onMounted(() => {
  address.value = userStore.currentUser?.city ?? ''
})

async function save(): Promise<void> {
  error.value = ''
  saved.value = false
  isSaving.value = true

  try {
    await userStore.updateAddress(address.value.trim() || null)
    saved.value = true
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pt-6">
    <div class="flex items-center gap-3 px-2">
      <button class="text-sm text-ink-faint" @click="router.back()">←</button>
      <span class="font-display text-lg text-ink">Адрес</span>
    </div>

    <div class="flex flex-col gap-3 px-2">
      <p class="text-xs text-ink-faint">
        Нужен, чтобы показывать расстояние до питомцев в поиске и маршрут после того, как вы
        договоритесь с другой стороной. Точный адрес виден только тем, с кем у вас уже есть чат или
        сделка.
      </p>

      <BaseInput v-model="address" label="Адрес" placeholder="Город, улица, дом" />

      <p v-if="userStore.currentUser?.city" class="text-xs text-ink-faint">
        Распознанный город: {{ userStore.currentUser.city }}
      </p>

      <p v-if="error" class="text-xs text-danger">{{ error }}</p>
      <p v-if="saved" class="text-xs text-teal">Сохранено</p>

      <BaseButton :disabled="isSaving" @click="save">
        {{ isSaving ? 'Сохраняем…' : 'Сохранить' }}
      </BaseButton>
    </div>
  </div>
</template>
