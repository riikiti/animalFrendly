import { test, expect } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

test('заводчик привязывает щенка к родителю, посторонний видит витрину продавца', async ({
  browser,
}) => {
  test.setTimeout(60_000)

  const parentName = `Мама${Date.now()}`
  const puppyName = `Щенок${Date.now()}`

  const breederContext = await browser.newContext()
  const breederPage = await breederContext.newPage()
  const breederPhone = randomPhone()

  await breederPage.goto('/register')
  await breederPage.getByPlaceholder('+7 926 123-45-67').fill(breederPhone)
  const passwordInputs = breederPage.locator('input[type="password"]')
  await passwordInputs.nth(0).fill('correct-password')
  await passwordInputs.nth(1).fill('correct-password')
  await breederPage.locator('input[type="checkbox"]').check()
  await breederPage.getByRole('button', { name: 'Продолжить' }).click()

  await breederPage.waitForURL('/onboarding/mode')
  await breederPage.getByText('Стать заводчиком').click()
  await breederPage.waitForURL('/marketplace/my-listings')

  // Первый листинг — родитель.
  await breederPage.getByRole('button', { name: 'Выставить питомца на продажу' }).click()
  await breederPage.getByPlaceholder('Рекс').fill(parentName)
  await breederPage.getByPlaceholder('15000').fill('30000')
  await breederPage.getByRole('button', { name: 'Создать' }).click()
  await expect(breederPage.getByText(parentName)).toBeVisible()
  await breederPage.getByRole('button', { name: 'Опубликовать' }).click()
  await expect(breederPage.getByText('Опубликован')).toBeVisible()

  // Второй листинг — щенок, привязанный к родителю через select.
  await breederPage.getByRole('button', { name: 'Выставить питомца на продажу' }).click()
  await breederPage.getByPlaceholder('Рекс').fill(puppyName)
  await breederPage.getByPlaceholder('15000').fill('15000')
  await breederPage.getByLabel('Родитель').selectOption({ label: parentName })
  await breederPage.getByRole('button', { name: 'Создать' }).click()
  await expect(breederPage.getByText(`Родитель: ${parentName}`)).toBeVisible()
  await breederPage.getByRole('button', { name: 'Опубликовать' }).click()

  const breederToken = await breederPage.evaluate(() => localStorage.getItem('af_token'))
  const meResponse = await breederPage.request.get('http://localhost:8000/api/v1/auth/me', {
    headers: { Authorization: `Bearer ${breederToken}` },
  })
  const breederId = (await meResponse.json()).id

  // Посторонний открывает витрину продавца и видит оба листинга.
  const strangerContext = await browser.newContext()
  const strangerPage = await strangerContext.newPage()
  const strangerPhone = randomPhone()

  await strangerPage.goto('/register')
  await strangerPage.getByPlaceholder('+7 926 123-45-67').fill(strangerPhone)
  const strangerPasswordInputs = strangerPage.locator('input[type="password"]')
  await strangerPasswordInputs.nth(0).fill('correct-password')
  await strangerPasswordInputs.nth(1).fill('correct-password')
  await strangerPage.locator('input[type="checkbox"]').check()
  await strangerPage.getByRole('button', { name: 'Продолжить' }).click()
  await strangerPage.waitForURL('/onboarding/mode')
  await strangerPage.getByText('Обычный профиль').click()
  await strangerPage.waitForURL('/pets/new')

  await strangerPage.goto(`/marketplace/sellers/${breederId}`)
  await expect(strangerPage.getByText(parentName, { exact: true })).toBeVisible()
  await expect(strangerPage.getByText(puppyName, { exact: true })).toBeVisible()
  await expect(strangerPage.getByText(`Щенок от ${parentName}`)).toBeVisible()
})
