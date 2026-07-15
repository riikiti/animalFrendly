import { computed, type ComputedRef } from 'vue'
import { useUserStore } from '@/entities/user/model'

interface UseStaff {
  isStaff: ComputedRef<boolean>
}

/**
 * Тонкий composable поверх useUserStore — используется для проактивного UX (скрыть ссылку на
 * админ-панель обычным пользователям). Не единственная защита: доступ к самим эндпоинтам
 * всегда проверяется на бэкенде через Gate 'access-admin-panel'/'moderate-reports'/'ban-users'.
 */
export function useStaff(): UseStaff {
  const store = useUserStore()

  const isStaff = computed(() => {
    const type = store.currentUser?.account_type
    return type === 'admin' || type === 'moderator'
  })

  return { isStaff }
}
