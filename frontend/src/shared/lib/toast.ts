import { reactive } from 'vue'

/**
 * Очередь тостов из секции «Тосты» борда D. Живёт вне компонентов, чтобы уведомление
 * можно было показать из перехватчика запросов или из стора, а не только из разметки.
 */
export type ToastTone = 'success' | 'error' | 'info' | 'warning' | 'loading' | 'compact'

export interface ToastOptions {
  tone?: ToastTone
  title: string
  description?: string
  /** Подпись действия справа — «Открыть», «Повторить», «Отменить». */
  actionLabel?: string
  onAction?: () => void
  /** Миллисекунды до автоскрытия. 0 — не скрывать (загрузка, ошибка с действием). */
  timeout?: number
}

export interface Toast extends ToastOptions {
  id: number
  tone: ToastTone
  /** Не гаснет сам — значит, обязан показать крестик, иначе его нечем убрать. */
  sticky: boolean
}

const toasts = reactive<Toast[]>([])

let nextId = 1

export const dismissToast = (id: number) => {
  const index = toasts.findIndex((toast) => toast.id === id)
  if (index !== -1) toasts.splice(index, 1)
}

export const pushToast = (options: ToastOptions) => {
  const tone = options.tone ?? 'info'
  const id = nextId++
  // Загрузку и ошибку не прячем сами: первая закрывается по завершении, вторая — руками.
  const timeout = options.timeout ?? (tone === 'loading' || tone === 'error' ? 0 : 4000)

  toasts.push({ ...options, tone, id, sticky: timeout === 0 && tone !== 'loading' })
  if (timeout > 0) setTimeout(() => dismissToast(id), timeout)

  return id
}

export const useToasts = () => toasts
