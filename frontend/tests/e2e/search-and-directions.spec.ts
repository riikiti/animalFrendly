import { execFileSync } from 'node:child_process'
import { test, expect } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

/**
 * Индексация в Meilisearch идёт через очередь (best-effort, см. IndexPetJob) — детерминированно
 * дожидаться отдельной задачи в E2E неудобно, поэтому синхронно гоняем полный реиндекс тем же
 * приёмом, что identity:create-staff в moderation.spec.ts.
 */
function reindexSearch(): void {
  const phpPath =
    'C:\\Users\\Ruslan\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe'
  execFileSync(phpPath, ['artisan', 'search:reindex'], { cwd: '../backend' })
}

test('a user sets their address, creates a pet and finds it via search', async ({ page }) => {
  const phone = randomPhone()
  const petName = `ПоискE2E${Date.now()}`

  await page.goto('/register')
  await page.getByPlaceholder('+7 926 123-45-67').fill(phone)
  const passwordInputs = page.locator('input[type="password"]')
  await passwordInputs.nth(0).fill('correct-password')
  await passwordInputs.nth(1).fill('correct-password')
  await page.locator('input[type="checkbox"]').check()
  await page.getByRole('button', { name: 'Продолжить' }).click()

  await page.waitForURL('/onboarding/mode')
  await page.getByText('Обычный профиль').click()
  await page.waitForURL('/pets/new')
  await page.getByPlaceholder('Рекс').fill(petName)
  await page.getByRole('button', { name: 'Создать анкету' }).click()
  await page.getByRole('button', { name: 'Готово' }).click()
  await page.waitForURL('/')

  // Нет реального ключа Yandex Geocoder в dev-окружении (null-фолбэк, см. NullGeocoderClient) —
  // проверяем, что адрес сохраняется, не то, что геокодер его распознал.
  await page.getByTitle('Адрес').click()
  await page.waitForURL('/profile')
  const saveResponse = page.waitForResponse(
    (res) => res.url().includes('/auth/me') && res.request().method() === 'PATCH',
  )
  await page.getByPlaceholder('Город, улица, дом').fill('Москва, Тверская улица, 1')
  await page.getByRole('button', { name: 'Сохранить' }).click()
  await saveResponse
  await expect(page.getByText('Сохранено')).toBeVisible()

  reindexSearch()

  await page.goto('/')
  await page.getByRole('button', { name: '🔍 Искать' }).click()
  await page.waitForURL('/search/pets')

  // Лента поиска общая для всей dev-БД — вместо просмотра всех страниц ищем по уникальной
  // кличке (полнотекстовый поиск Meilisearch по имени питомца).
  await page.getByPlaceholder('Например, Рекс').fill(petName)
  await page.getByPlaceholder('Например, Рекс').blur()
  await expect(page.getByText(petName)).toBeVisible()
})
