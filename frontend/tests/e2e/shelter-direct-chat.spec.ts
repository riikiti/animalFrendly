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

test('adopter contacts a shelter directly from an animal card, shelter replies from /chats', async ({
  browser,
}) => {
  test.setTimeout(60_000)

  const shelterName = `Добрые лапы ${Date.now()}`
  const animalName = `Рыжик${Date.now()}`
  const adopterName = `Мария${Date.now()}`

  // Приют регистрируется, верифицируется (модератор), публикует животное — тот же путь,
  // что и в shelter-management.spec.ts, только напрямую через API, раз сама верификация
  // здесь не проверяется.
  const shelterContext = await browser.newContext()
  const shelterPage = await shelterContext.newPage()
  const shelterPhone = randomPhone()

  await shelterPage.goto('/register')
  await shelterPage.getByPlaceholder('+7 926 123-45-67').fill(shelterPhone)
  const shelterPasswordInputs = shelterPage.locator('input[type="password"]')
  await shelterPasswordInputs.nth(0).fill('correct-password')
  await shelterPasswordInputs.nth(1).fill('correct-password')
  await shelterPage.locator('input[type="checkbox"]').check()
  await shelterPage.getByRole('button', { name: 'Продолжить' }).click()
  await shelterPage.waitForURL('/onboarding/mode')
  await shelterPage.getByText('Зарегистрировать приют').click()
  await shelterPage.waitForURL('/shelter/mine')

  await shelterPage.getByPlaceholder('Добрые лапы').fill(shelterName)
  await shelterPage.getByRole('button', { name: 'Создать приют' }).click()
  await expect(shelterPage.getByText('На рассмотрении модератора')).toBeVisible()

  const shelterToken = await shelterPage.evaluate(() => localStorage.getItem('af_token'))
  const shelterId = (
    await (
      await shelterPage.request.get('http://localhost:8000/api/v1/shelters/me', {
        headers: { Authorization: `Bearer ${shelterToken}` },
      })
    ).json()
  ).data.id

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
  await staffPage.getByText('Приюты ждут верификации').click()
  await staffPage.waitForURL('/admin/shelters')
  const shelterCard = staffPage.locator('div', { hasText: shelterName }).last()
  await shelterCard.getByRole('button', { name: 'Подтвердить' }).click()
  await expect(staffPage.getByText(shelterName)).not.toBeVisible()

  await shelterPage.reload()
  await expect(shelterPage.getByText('Верифицирован')).toBeVisible()

  await shelterPage.getByPlaceholder('Рыжик').fill(animalName)
  await shelterPage.getByRole('button', { name: 'Собака' }).click()
  await shelterPage.getByRole('button', { name: 'Добавить животное' }).click()
  await expect(shelterPage.getByText(animalName)).toBeVisible()

  // Усыновитель открывает анкету животного и жмёт «Связаться» — сразу попадает в чат с
  // прикреплённой карточкой этого животного, без промежуточной витрины.
  const adopterContext = await browser.newContext()
  const adopterPage = await adopterContext.newPage()
  const adopterPhone = randomPhone()

  await adopterPage.goto('/register')
  await adopterPage.getByPlaceholder('+7 926 123-45-67').fill(adopterPhone)
  const adopterPasswordInputs = adopterPage.locator('input[type="password"]')
  await adopterPasswordInputs.nth(0).fill('correct-password')
  await adopterPasswordInputs.nth(1).fill('correct-password')
  await adopterPage.locator('input[type="checkbox"]').check()
  await adopterPage.getByRole('button', { name: 'Продолжить' }).click()
  await adopterPage.waitForURL('/onboarding/mode')
  await adopterPage.getByText('Обычный профиль').click()
  await adopterPage.waitForURL('/pets/new')

  await adopterPage.goto('/profile')
  await adopterPage.getByPlaceholder('Как вас называть').fill(adopterName)
  await adopterPage.getByRole('button', { name: 'Сохранить' }).click()
  await expect(adopterPage.getByText('Сохранено')).toBeVisible()

  await adopterPage.goto('/shelters')
  await adopterPage.locator('.card', { hasText: animalName }).click()
  await adopterPage.waitForURL(/\/shelter-animals\/.+/)
  await adopterPage.getByRole('button', { name: 'Связаться' }).click()
  await adopterPage.waitForURL(/\/chat\/shelter\/.+/)
  await expect(adopterPage.getByText(animalName)).toBeVisible()

  await adopterPage.getByPlaceholder('Сообщение…').fill('Расскажите про Рыжика')
  await adopterPage.getByPlaceholder('Сообщение…').press('Enter')
  await expect(adopterPage.getByText('Расскажите про Рыжика')).toBeVisible()

  // Приют видит новую беседу во вкладке «Чаты» с именем усыновителя и отвечает.
  await shelterPage.goto('/chats')
  await expect(shelterPage.getByText(adopterName)).toBeVisible()
  await shelterPage.getByText(adopterName).click()
  await expect(shelterPage.getByText('Расскажите про Рыжика')).toBeVisible()

  await shelterPage.getByPlaceholder('Сообщение…').fill('Конечно, пишите!')
  await shelterPage.getByPlaceholder('Сообщение…').press('Enter')
  await expect(adopterPage.getByText('Конечно, пишите!')).toBeVisible({ timeout: 15_000 })

  // «Написать в приют» с витрины (без привязки к животному) переиспользует ту же беседу
  // «приют-посетитель» — повторный визит не плодит дубликаты.
  await adopterPage.goto(`/shelters/${shelterId}`)
  await adopterPage.getByRole('button', { name: 'Написать в приют' }).click()
  await adopterPage.waitForURL(/\/chat\/shelter\/.+/)
  await expect(adopterPage.getByText('Расскажите про Рыжика')).toBeVisible()
})
