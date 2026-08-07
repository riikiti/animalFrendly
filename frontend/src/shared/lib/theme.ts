import { ref, watch } from 'vue'

export type ThemePreference = 'system' | 'light' | 'dark'

const STORAGE_KEY = 'af_theme'

const isPreference = (value: unknown): value is ThemePreference =>
  value === 'system' || value === 'light' || value === 'dark'

const stored = localStorage.getItem(STORAGE_KEY)

export const themePreference = ref<ThemePreference>(isPreference(stored) ? stored : 'system')

/**
 * Тему выбирает атрибут data-theme на <html> (см. shared/ui/tokens.css). Без атрибута
 * берётся системная — поэтому «Системная» его снимает, а не выставляет вычисленное значение.
 */
const apply = (preference: ThemePreference) => {
  const root = document.documentElement
  if (preference === 'system') root.removeAttribute('data-theme')
  else root.setAttribute('data-theme', preference)
}

apply(themePreference.value)

watch(themePreference, (preference) => {
  localStorage.setItem(STORAGE_KEY, preference)
  apply(preference)
})
