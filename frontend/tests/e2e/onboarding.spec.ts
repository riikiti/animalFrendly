import { test, expect } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

test.describe('онбординг: регистрация и вход', () => {
  test('пользователь регистрируется, попадает на главную и может выйти и войти снова', async ({
    page,
  }) => {
    const phone = randomPhone()

    await page.goto('/register')
    await page.getByText('Я — владелец').click()
    await page.getByPlaceholder('+7 926 123-45-67').fill(phone)

    const passwordInputs = page.locator('input[type="password"]')
    await passwordInputs.nth(0).fill('correct-password')
    await passwordInputs.nth(1).fill('correct-password')
    await page.locator('input[type="checkbox"]').check()

    await page.getByRole('button', { name: 'Продолжить' }).click()

    await page.waitForURL('/')
    await expect(page.getByText(phone)).toBeVisible()

    await page.getByRole('button', { name: 'Выйти' }).click()
    await page.waitForURL('/login')

    await page.getByPlaceholder('+7 926 123-45-67').fill(phone)
    await page.locator('input[type="password"]').fill('correct-password')
    await page.getByRole('button', { name: 'Войти' }).click()

    await page.waitForURL('/')
    await expect(page.getByText(phone)).toBeVisible()
  })
})
