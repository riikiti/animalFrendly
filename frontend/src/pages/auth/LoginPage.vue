<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { MessageSquare, PawPrint } from 'lucide-vue-next'
import LoginForm from '@/features/auth/login-user/LoginForm.vue'
import * as userApi from '@/entities/user/api'
import { useUserStore } from '@/entities/user/model'
import { setToken } from '@/shared/lib/tokenStorage'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const providers = ref<string[]>([])
const socialError = ref('')

const providerLabels: Record<string, string> = {
  google: 'Google',
  vkontakte: 'VK',
}

const apiUrl = import.meta.env.VITE_API_URL ?? ''

onMounted(async () => {
  // Возврат от провайдера: токен приезжает параметром, сразу прячем его из адресной строки.
  const token = route.query.social_token
  if (typeof token === 'string' && token !== '') {
    setToken(token)
    await userStore.fetchCurrentUser()
    await router.replace({ name: 'home' })

    return
  }

  const failure = route.query.social_error
  if (typeof failure === 'string') socialError.value = failure

  try {
    providers.value = (await userApi.socialProviders()).data
  } catch {
    // Список провайдеров не критичен: без него просто не покажем кнопки.
    providers.value = []
  }
})
</script>

<template>
  <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 pt-6 pb-8 lg:max-w-lg">
    <!-- Приветственный блок вместо фотографии из макета: своих изображений в проекте нет,
    поэтому бренд показываем на мягкой коралловой подложке. -->
    <div class="flex h-[172px] shrink-0 items-center justify-center rounded-3xl bg-accent-soft">
      <span
        class="inline-flex items-center gap-2 rounded-full bg-surface/90 py-1.5 pr-3.5 pl-2 shadow-sm"
      >
        <span class="grid size-6 place-items-center rounded-lg bg-accent text-accent-ink">
          <PawPrint class="size-3.5" aria-hidden="true" />
        </span>
        <span class="font-display text-[15px] font-bold text-ink">AnimalFriendly</span>
      </span>
    </div>

    <div class="mt-6 flex flex-col gap-1.5">
      <h1 class="font-display text-[28px] leading-tight font-bold text-ink">С возвращением</h1>
      <p class="text-sm text-ink-soft">Питомцы рядом уже ждут новых знакомств</p>
    </div>

    <LoginForm class="mt-6" />

    <BaseAlert v-if="socialError" tone="error" class="mt-4">{{ socialError }}</BaseAlert>

    <BaseButton
      variant="outline"
      size="lg"
      block
      class="mt-3"
      @click="router.push({ name: 'login-sms' })"
    >
      <MessageSquare class="size-[17px]" aria-hidden="true" />
      Войти по коду из SMS
    </BaseButton>

    <template v-if="providers.length > 0">
      <div class="mt-5 flex items-center gap-3">
        <span class="h-px flex-1 bg-hairline" />
        <span class="text-xs text-ink-faint">или</span>
        <span class="h-px flex-1 bg-hairline" />
      </div>

      <div class="mt-4 flex gap-2.5">
        <a
          v-for="provider in providers"
          :key="provider"
          :href="`${apiUrl}/api/v1/auth/social/${provider}/redirect`"
          class="flex h-12 flex-1 items-center justify-center rounded-[14px] border border-hairline bg-surface text-[13.5px] font-semibold text-ink transition-colors hover:bg-surface-soft"
        >
          {{ providerLabels[provider] ?? provider }}
        </a>
      </div>
    </template>

    <p class="mt-auto pt-8 text-center text-[13.5px] text-ink-soft">
      Нет аккаунта?
      <RouterLink :to="{ name: 'register' }" class="font-bold text-accent-text"
        >Зарегистрироваться</RouterLink
      >
    </p>
  </div>
</template>
