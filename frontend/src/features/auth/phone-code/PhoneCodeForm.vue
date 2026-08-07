<script setup lang="ts">
import { computed, ref } from 'vue'
import * as userApi from '@/entities/user/api'
import { ApiError } from '@/shared/api/http'
import BaseAlert from '@/shared/ui/components/BaseAlert.vue'
import BaseButton from '@/shared/ui/components/BaseButton.vue'
import BaseInput from '@/shared/ui/components/BaseInput.vue'
import BaseOtpInput from '@/shared/ui/components/BaseOtpInput.vue'

/**
 * Два шага одного сценария: сначала телефон, потом код из СМС. Используется и для входа
 * по коду, и для восстановления пароля — отличается только purpose и тем, что делает
 * родитель после успешной проверки.
 */
const props = defineProps<{
  purpose: userApi.PhoneCodePurpose
  /** Подпись кнопки на шаге с кодом. */
  submitLabel: string
  busy?: boolean
  error?: string
}>()

const emit = defineEmits<{ submit: [payload: { phone: string; code: string }] }>()

const phone = ref('')
const code = ref('')
const step = ref<'phone' | 'code'>('phone')
const requesting = ref(false)
const localError = ref('')
const resendAfter = ref(0)

const shownError = computed(() => props.error || localError.value)

let resendTimer: ReturnType<typeof setInterval> | null = null

const startResendCountdown = () => {
  resendAfter.value = 60
  if (resendTimer) clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendAfter.value -= 1
    if (resendAfter.value <= 0 && resendTimer) clearInterval(resendTimer)
  }, 1000)
}

const requestCode = async () => {
  localError.value = ''
  requesting.value = true

  try {
    await userApi.requestPhoneCode(phone.value, props.purpose)
    step.value = 'code'
    startResendCountdown()
  } catch (e) {
    localError.value =
      e instanceof ApiError ? e.message : 'Не удалось отправить код. Попробуйте ещё раз.'
  } finally {
    requesting.value = false
  }
}
</script>

<template>
  <form class="flex flex-col gap-3.5" @submit.prevent>
    <template v-if="step === 'phone'">
      <BaseInput
        v-model="phone"
        label="Номер телефона"
        type="tel"
        inputmode="tel"
        placeholder="+7 926 123-45-67"
      />

      <BaseAlert v-if="shownError" tone="error">{{ shownError }}</BaseAlert>

      <BaseButton
        size="lg"
        block
        class="mt-1"
        :loading="requesting"
        :disabled="phone.length < 5"
        @click="requestCode"
      >
        Получить код
      </BaseButton>
    </template>

    <template v-else>
      <p class="text-sm text-ink-soft">
        Отправили код на {{ phone }}.
        <button type="button" class="font-bold text-accent-text" @click="step = 'phone'">
          Изменить
        </button>
      </p>

      <BaseOtpInput v-model="code" label="Код из СМС" autofocus />

      <BaseAlert v-if="shownError" tone="error">{{ shownError }}</BaseAlert>

      <BaseButton
        size="lg"
        block
        class="mt-1"
        :loading="busy"
        :disabled="code.length < 4"
        @click="emit('submit', { phone, code })"
      >
        {{ submitLabel }}
      </BaseButton>

      <BaseButton
        variant="ghost"
        block
        :disabled="resendAfter > 0 || requesting"
        @click="requestCode"
      >
        {{ resendAfter > 0 ? `Отправить снова через ${resendAfter} с` : 'Отправить код снова' }}
      </BaseButton>
    </template>
  </form>
</template>
