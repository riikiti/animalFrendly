import { test, expect } from '@playwright/test'

// Бэкенд без реальных ключей ЮKassa возвращает return_url как есть (см. NullYookassaClient) —
// платёжная страница ЮKassa не задействована. Вебхук эмулируется прямым запросом к API, id
// платежа детерминирован от идемпотентного ключа "{subscriptionId}:create" (см.
// NullYookassaClient::createPayment). Требует засеянного тарифа "plus" (SubscriptionPlanSeeder).
const API_URL = 'http://localhost:8000'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

test('оформление подписки → оплата с сохранением метода → активна → отмена автопродления', async ({
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
  await page.getByPlaceholder('Рекс').fill(`Подписчик${Date.now()}`)
  await page.getByRole('button', { name: 'Создать анкету' }).click()
  await page.getByRole('button', { name: 'Готово' }).click()
  await page.waitForURL('/')

  await page.getByRole('button', { name: 'Тариф' }).click()
  await page.waitForURL('/subscription/status')
  await expect(page.getByText('У вас бесплатный тариф')).toBeVisible()

  await page.getByRole('button', { name: 'Смотреть тарифы' }).click()
  await page.waitForURL('/subscription/plans')

  const plusCard = page.locator('.card', { hasText: 'Plus' })
  await plusCard.getByRole('button', { name: 'Оформить' }).click()

  // NullYookassaClient возвращает confirmation_url = return_url — браузер сразу попадает на
  // страницу статуса подписки, минуя хостинговую страницу оплаты.
  await page.waitForURL('/subscription/status')
  await expect(page.getByText('Ожидает оплаты')).toBeVisible()

  const token = await page.evaluate(() => localStorage.getItem('af_token'))
  const meResponse = await page.request.get(`${API_URL}/api/v1/subscriptions/me`, {
    headers: { Authorization: `Bearer ${token}` },
  })
  const subscriptionId = (await meResponse.json()).data.subscription.id as string
  const yookassaPaymentId = `local-${subscriptionId}:create`

  const webhookResponse = await page.request.post(`${API_URL}/api/v1/payments/webhooks/yookassa`, {
    data: {
      event: 'payment.succeeded',
      object: {
        id: yookassaPaymentId,
        status: 'succeeded',
        payment_method: { id: `pm-${subscriptionId}`, saved: true },
      },
    },
  })
  expect(webhookResponse.ok()).toBeTruthy()

  await expect(page.getByText('Активна')).toBeVisible({ timeout: 10_000 })

  await page.getByRole('button', { name: 'Отменить автопродление' }).click()
  await expect(page.getByText('Автопродление отключено')).toBeVisible()
})
