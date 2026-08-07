import { execFileSync } from 'node:child_process'
import { test, expect } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

/**
 * Публичная регистрация не может создать admin/moderator (см. RegisterRequest) — единственный
 * способ завести staff-аккаунт для E2E — консольная команда identity:create-staff, тот же
 * приём, что и при ручном разворачивании (см. план фазы Moderation+Admin).
 */
function createStaffAccount(phone: string, password: string): void {
  const phpPath =
    'C:\\Users\\Ruslan\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe'
  execFileSync(phpPath, ['artisan', 'identity:create-staff', phone, password, '--role=moderator'], {
    cwd: '../backend',
  })
}

test('a user reports a pet, a moderator reviews it and bans the reporter target', async ({
  browser,
}) => {
  const reporterContext = await browser.newContext()
  const reporterPage = await reporterContext.newPage()
  const reporterPhone = randomPhone()

  await reporterPage.goto('/register')
  await reporterPage.getByPlaceholder('+7 926 123-45-67').fill(reporterPhone)
  const passwordInputs = reporterPage.locator('input[type="password"]')
  await passwordInputs.nth(0).fill('correct-password')
  await passwordInputs.nth(1).fill('correct-password')
  await reporterPage.locator('input[type="checkbox"]').check()
  await reporterPage.getByRole('button', { name: 'Продолжить' }).click()

  await reporterPage.waitForURL('/onboarding/mode')
  await reporterPage.getByText('Обычный профиль').click()
  await reporterPage.waitForURL('/pets/new')
  await reporterPage.getByPlaceholder('Рекс').fill(`Жертва${Date.now()}`)
  await reporterPage.getByRole('button', { name: 'Создать анкету' }).click()
  await reporterPage.getByRole('button', { name: 'Готово' }).click()
  await reporterPage.waitForURL('/')

  // Своя же анкета — единственный кандидат, доступный сразу после регистрации второго
  // пользователя ещё не создан, поэтому репортим напрямую через API запрос от лица
  // авторизованной страницы (тот же токен, что уже в localStorage).
  const token = await reporterPage.evaluate(() => localStorage.getItem('af_token'))
  const reportResponse = await reporterPage.request.post('http://localhost:8000/api/v1/reports', {
    headers: { Authorization: `Bearer ${token}` },
    data: { target_type: 'user', target_id: 'placeholder', reason: 'spam', comment: 'Тест' },
  })
  expect(reportResponse.ok()).toBeTruthy()

  const staffPhone = randomPhone()
  createStaffAccount(staffPhone, 'staff-password')

  const staffContext = await browser.newContext()
  const staffPage = await staffContext.newPage()
  await staffPage.goto('/login')
  await staffPage.getByPlaceholder('+7 926 123-45-67').fill(staffPhone)
  await staffPage.locator('input[type="password"]').fill('staff-password')
  await staffPage.getByRole('button', { name: 'Войти', exact: true }).click()
  await staffPage.waitForURL('/')

  await staffPage.getByRole('button', { name: 'Админ' }).click()
  await staffPage.waitForURL('/admin')
  await expect(staffPage.getByText('Жалобы на рассмотрении')).toBeVisible()

  await staffPage.getByText('Жалобы на рассмотрении').click()
  await staffPage.waitForURL('/admin/reports')
  // Лента жалоб общая для всей dev-БД (не изолирована миграциями между прогонами) — проверяем
  // хотя бы одну карточку с нашим комментарием, а не точное количество.
  await expect(staffPage.getByText('Тест').first()).toBeVisible()

  const reviewResponse = staffPage.waitForResponse(
    (res) => res.url().includes('/review') && res.request().method() === 'POST',
  )
  await staffPage.getByRole('button', { name: 'Рассмотреть' }).first().click()
  await reviewResponse

  await staffPage.goto('/admin/audit-log')
  await expect(staffPage.getByText('report.reviewed').first()).toBeVisible()
})
