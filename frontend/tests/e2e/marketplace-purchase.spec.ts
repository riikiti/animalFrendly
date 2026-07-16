import { test, expect, type BrowserContext, type Page } from '@playwright/test'

// Бэкенд без реальных ключей ЮKassa возвращает return_url как есть (см.
// NullYookassaClient) — платёжная страница ЮKassa не задействована, что и позволяет
// прогнать флоу целиком в E2E. Вебхук эмулируется прямым запросом к API, id платежа
// детерминирован от order_id (см. NullYookassaClient::createPayment).
const API_URL = 'http://localhost:8000'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

async function registerWithThrowawayPet(context: BrowserContext, petName: string): Promise<Page> {
  const page = await context.newPage()
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
  await page.getByPlaceholder('Рекс').fill(petName)
  await page.getByRole('button', { name: 'Создать анкету' }).click()
  await page.getByRole('button', { name: 'Готово' }).click()
  await page.waitForURL('/')

  return page
}

test('листинг → покупка → эскроу-оплата → подтверждение обеими сторонами', async ({ browser }) => {
  // Под полной параллельной нагрузкой всего E2E-набора (8 браузеров Chromium одновременно)
  // на этой машине заметно возрастает джиттер CPU-шедулинга — обработка вебхука/финального
  // подтверждения через очередь может занять заметно дольше, чем в изолированном прогоне
  // (см. тот же приём в matching-and-chat.spec.ts — тест стабилен и один, и при малой нагрузке).
  test.setTimeout(60_000)

  const contextSeller = await browser.newContext()
  const contextBuyer = await browser.newContext()

  const sellerPage = await registerWithThrowawayPet(contextSeller, `Продавец-питомец${Date.now()}`)
  const buyerPage = await registerWithThrowawayPet(contextBuyer, `Покупатель-питомец${Date.now()}`)

  const listingPetName = `Барон${Date.now()}`

  await sellerPage.goto('/marketplace/my-listings')
  await sellerPage.getByRole('button', { name: 'Выставить питомца на продажу' }).click()
  await sellerPage.getByPlaceholder('Рекс').fill(listingPetName)
  await sellerPage.getByPlaceholder('15000').fill('15000')
  await sellerPage.getByRole('button', { name: 'Создать' }).click()

  await expect(sellerPage.getByText(listingPetName)).toBeVisible()
  await sellerPage.getByRole('button', { name: 'Опубликовать' }).click()
  await expect(sellerPage.getByText('Опубликован')).toBeVisible()

  await buyerPage.goto('/marketplace')
  ;(await buyerPage.waitForSelector(`text=${listingPetName}`)).scrollIntoViewIfNeeded()

  const listingCard = buyerPage.locator('.card', { hasText: listingPetName })
  await listingCard.getByRole('button', { name: 'Купить' }).click()

  // NullYookassaClient возвращает confirmation_url = return_url — браузер сразу попадает
  // на страницу заказа, минуя хостинговую страницу оплаты.
  await buyerPage.waitForURL(/\/orders\//)
  await expect(buyerPage.getByText('Ожидает оплаты')).toBeVisible()

  const orderId = buyerPage.url().split('/orders/')[1]
  const yookassaPaymentId = `local-${orderId}:create`

  const webhookResponse = await buyerPage.request.post(
    `${API_URL}/api/v1/payments/webhooks/yookassa`,
    {
      data: { event: 'payment.succeeded', object: { id: yookassaPaymentId, status: 'succeeded' } },
    },
  )
  expect(webhookResponse.ok()).toBeTruthy()

  await expect(buyerPage.getByText('Оплачено, на удержании')).toBeVisible({ timeout: 10_000 })

  await buyerPage.getByRole('button', { name: 'Подтвердить получение' }).click()
  await expect(buyerPage.getByText('Вы подтвердили сделку, ждём вторую сторону')).toBeVisible()

  await sellerPage.goto('/orders')
  await sellerPage.getByRole('button', { name: 'Продажи' }).click()
  await sellerPage.getByText('Оплачено, на удержании').click()

  await sellerPage.getByRole('button', { name: 'Подтвердить получение' }).click()
  await expect(sellerPage.getByText('Завершена')).toBeVisible({ timeout: 45_000 })
})
