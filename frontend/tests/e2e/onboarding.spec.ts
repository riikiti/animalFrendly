import { test, expect } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

test.describe('онбординг: регистрация, анкета питомца, вход', () => {
  test('пользователь регистрируется, заводит анкету питомца, попадает в ленту и может выйти и войти снова', async ({
    page,
  }) => {
    const phone = randomPhone()

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
    await expect(page.getByText('Анкета питомца')).toBeVisible()

    await page.getByPlaceholder('Рекс').fill('Барс')
    await page.getByRole('button', { name: 'Создать анкету' }).click()

    await page.getByRole('button', { name: 'Готово' }).click()
    await page.waitForURL('/')
    await expect(page.getByText('AnimalFriendly')).toBeVisible()

    await page.goto('/profile')
    await page.getByRole('button', { name: 'Выйти' }).click()
    await page.waitForURL('/login')

    await page.getByPlaceholder('+7 926 123-45-67').fill(phone)
    await page.locator('input[type="password"]').fill('correct-password')
    await page.getByRole('button', { name: 'Войти' }).click()

    await page.waitForURL('/')
    await expect(page.getByText('AnimalFriendly')).toBeVisible()
  })
})
