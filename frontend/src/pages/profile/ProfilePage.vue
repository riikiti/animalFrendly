<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BottomNav from '@/widgets/BottomNav.vue'
import { useUserStore } from '@/entities/user/model'
import { ApiError } from '@/shared/api/http'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'

const accountTypeLabels: Record<string, string> = {
  owner: 'Владелец',
  breeder: 'Заводчик',
  shelter: 'Приют',
  admin: 'Администратор',
  moderator: 'Модератор',
}

const router = useRouter()
const userStore = useUserStore()

const name = ref('')
const address = ref('')
const isSaving = ref(false)
const error = ref('')
const saved = ref(false)

onMounted(() => {
  name.value = userStore.currentUser?.name ?? ''
  address.value = userStore.currentUser?.city ?? ''
})

async function save(): Promise<void> {
  error.value = ''
  saved.value = false
  isSaving.value = true

  try {
    await userStore.updateProfile({
      name: name.value.trim() || null,
      address: address.value.trim() || null,
    })
    saved.value = true
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div
    class="mx-auto flex min-h-screen max-w-sm flex-col gap-4 px-4 pb-0 pt-6 md:max-w-lg lg:max-w-2xl lg:px-8"
  >
    <div class="flex items-center justify-between px-2">
      <span class="font-display text-lg text-ink">Профиль</span>
    </div>

    <div class="flex flex-col gap-3 px-2">
      <div class="flex flex-col gap-1 rounded-2xl bg-surface-soft p-3 text-xs text-ink-soft">
        <span>Телефон: {{ userStore.currentUser?.phone }}</span>
        <span v-if="userStore.currentUser?.email">Почта: {{ userStore.currentUser.email }}</span>
        <span v-if="userStore.currentUser?.account_type">
          Роль:
          {{
            accountTypeLabels[userStore.currentUser.account_type] ??
            userStore.currentUser.account_type
          }}
        </span>
      </div>

      <BaseInput v-model="name" label="Имя" placeholder="Как вас называть" />

      <p class="text-xs text-ink-faint">
        Адрес нужен, чтобы показывать расстояние до питомцев в поиске и маршрут после того, как вы
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

    <div
      v-if="userStore.currentUser?.account_type === 'shelter'"
      class="flex flex-col gap-3 border-t border-hairline px-2 pt-4"
    >
      <span class="font-display text-lg text-ink">Приют</span>
      <BaseButton variant="ghost" @click="router.push({ name: 'my-shelter' })">
        Управление приютом
      </BaseButton>
    </div>

    <div class="flex flex-col gap-3 border-t border-hairline px-2 pt-4">
      <span class="font-display text-lg text-ink">Питомцы</span>
      <p class="text-xs text-ink-faint">
        Бесплатно доступна одна анкета питомца. По подписке можно завести сколько угодно.
      </p>
      <BaseButton variant="ghost" @click="router.push({ name: 'create-pet' })">
        Добавить ещё одного питомца
      </BaseButton>
    </div>

    <div class="-mx-4 mt-auto">
      <BottomNav />
    </div>
  </div>
</template>
