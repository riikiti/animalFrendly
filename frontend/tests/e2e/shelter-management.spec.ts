import { execFileSync } from 'node:child_process'
import { test, expect } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

function createStaffAccount(phone: string, password: string): void {
  const phpPath =
    'C:\\Users\\Ruslan\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe'
  execFileSync(phpPath, ['artisan', 'identity:create-staff', phone, password, '--role=moderator'], {
    cwd: '../backend',
  })
}

test('a shelter registers, gets verified, publishes an animal and approves an adoption request', async ({
  browser,
}) => {
  test.setTimeout(60_000)

  const shelterName = `Добрые лапы ${Date.now()}`
  const animalName = `Рыжик${Date.now()}`

  // Владелец приюта регистрируется и создаёт приют — своей анкеты питомца для мэтчинга ему
  // не нужно, поэтому уходим с /pets/new сразу на страницу приюта.
  const shelterContext = await browser.newContext()
  const shelterPage = await shelterContext.newPage()
  const shelterPhone = randomPhone()

  await shelterPage.goto('/register')
  await shelterPage.getByText('Я — приют').click()
  await shelterPage.getByPlaceholder('+7 926 123-45-67').fill(shelterPhone)
  const shelterPasswordInputs = shelterPage.locator('input[type="password"]')
  await shelterPasswordInputs.nth(0).fill('correct-password')
  await shelterPasswordInputs.nth(1).fill('correct-password')
  await shelterPage.locator('input[type="checkbox"]').check()
  await shelterPage.getByRole('button', { name: 'Продолжить' }).click()
  await shelterPage.waitForURL('/pets/new')

  await shelterPage.goto('/shelter/mine')
  await shelterPage.getByPlaceholder('Добрые лапы').fill(shelterName)
  await shelterPage.getByRole('button', { name: 'Создать приют' }).click()
  await expect(shelterPage.getByText('На рассмотрении модератора')).toBeVisible()

  // Модератор находит приют в списке на верификации и подтверждает его.
  const staffPhone = randomPhone()
  createStaffAccount(staffPhone, 'staff-password')

  const staffContext = await browser.newContext()
  const staffPage = await staffContext.newPage()
  await staffPage.goto('/login')
  await staffPage.getByPlaceholder('+7 926 123-45-67').fill(staffPhone)
  await staffPage.locator('input[type="password"]').fill('staff-password')
  await staffPage.getByRole('button', { name: 'Войти' }).click()
  await staffPage.waitForURL('/')

  await staffPage.getByRole('button', { name: 'Админ' }).click()
  await staffPage.waitForURL('/admin')
  await staffPage.getByText('Приюты ждут верификации').click()
  await staffPage.waitForURL('/admin/shelters')

  const shelterCard = staffPage.locator('div', { hasText: shelterName }).last()
  await shelterCard.getByRole('button', { name: 'Подтвердить' }).click()
  await expect(staffPage.getByText(shelterName)).not.toBeVisible()

  // Приют теперь верифицирован — добавляет животное.
  await shelterPage.reload()
  await expect(shelterPage.getByText('Верифицирован')).toBeVisible()

  await shelterPage.getByPlaceholder('Рыжик').fill(animalName)
  await shelterPage.getByRole('button', { name: 'Собака' }).click()
  await shelterPage.getByRole('button', { name: 'Добавить животное' }).click()
  await expect(shelterPage.getByText(animalName)).toBeVisible()

  // Усыновитель находит животное в общей ленте приютов и подаёт заявку.
  const adopterContext = await browser.newContext()
  const adopterPage = await adopterContext.newPage()
  const adopterPhone = randomPhone()

  await adopterPage.goto('/register')
  await adopterPage.getByText('Я — владелец').click()
  await adopterPage.getByPlaceholder('+7 926 123-45-67').fill(adopterPhone)
  const adopterPasswordInputs = adopterPage.locator('input[type="password"]')
  await adopterPasswordInputs.nth(0).fill('correct-password')
  await adopterPasswordInputs.nth(1).fill('correct-password')
  await adopterPage.locator('input[type="checkbox"]').check()
  await adopterPage.getByRole('button', { name: 'Продолжить' }).click()
  await adopterPage.waitForURL('/pets/new')

  await adopterPage.goto('/shelters')
  const animalCard = adopterPage.locator('.card', { hasText: animalName })
  await animalCard.getByRole('button', { name: 'Оставить заявку' }).click()
  await adopterPage
    .getByPlaceholder('Расскажите о себе и опыте содержания животных')
    .fill('Хочу забрать домой')
  await animalCard.getByRole('button', { name: 'Отправить' }).click()
  await expect(animalCard.getByText('Заявка отправлена')).toBeVisible()

  // Приют видит заявку и одобряет её.
  await shelterPage.reload()
  await expect(shelterPage.getByText('Хочу забрать домой')).toBeVisible()
  await shelterPage.getByRole('button', { name: 'Одобрить' }).click()
  await expect(shelterPage.getByText('Хочу забрать домой')).not.toBeVisible()
})
