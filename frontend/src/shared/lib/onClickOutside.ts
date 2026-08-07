import { onBeforeUnmount, watch, type Ref } from 'vue'

/**
 * Закрывает всплывающий блок по клику мимо него. Слушатель вешается только пока блок
 * открыт, поэтому на странице с десятком поповеров не копятся обработчики.
 */
export const onClickOutside = (
  target: Ref<HTMLElement | null>,
  active: Ref<boolean>,
  handler: () => void,
) => {
  const listener = (event: MouseEvent) => {
    const element = target.value
    if (element && !element.contains(event.target as Node)) handler()
  }

  watch(active, (open) => {
    if (open) document.addEventListener('mousedown', listener)
    else document.removeEventListener('mousedown', listener)
  })

  onBeforeUnmount(() => document.removeEventListener('mousedown', listener))
}
