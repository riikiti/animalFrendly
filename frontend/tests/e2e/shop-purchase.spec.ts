import { test, expect, type BrowserContext, type Page } from '@playwright/test'

// Как и в сделке маркетплейса: без реальных ключей ЮKassa бэкенд возвращает return_url
// как есть (NullYookassaClient), поэтому платёжная страница не задействована, а вебхук
// эмулируется прямым запросом — id платежа детерминирован от id заказа.
const API_URL = 'http://localhost:8000'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

async function register(context: BrowserContext, petName: string): Promise<Page> {
  const page = await context.newPage()

  await page.goto('/register')
  await page.getByPlaceholder('+7 926 123-45-67').fill(randomPhone())

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

/** Заказы покупателя через API: после оплаты страница уходит на редирект, и тело ответа теряется. */
async function fetchOrders(page: Page): Promise<Array<Record<string, string>>> {
  return page.evaluate(async (api) => {
    const response = await fetch(`${api}/api/v1/shop/orders`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('af_token')}`,
      },
    })

    return (await response.json()).data
  }, API_URL)
}

test('товар → корзина → заказ с доставкой → эскроу → подтверждение обеими сторонами', async ({
  browser,
}) => {
  test.setTimeout(120_000)

  const productTitle = `Корм${Date.now()}`

  const sellerContext = await browser.newContext()
  const sellerPage = await register(sellerContext, `Продавец${Date.now()}`)

  // Продавец публикует товар.
  await sellerPage.goto('/shop/my-products')
  await sellerPage.getByRole('button', { name: 'Добавить товар' }).click()
  await sellerPage.getByRole('combobox', { name: 'Категория' }).click()
  await sellerPage.getByRole('option', { name: 'Корма и лакомства' }).click()
  await sellerPage.getByPlaceholder('Корм для щенков').fill(productTitle)
  await sellerPage.getByPlaceholder('1290').fill('1290')
  await sellerPage.getByRole('button', { name: 'Опубликовать' }).click()

  await expect(sellerPage.getByText(productTitle)).toBeVisible()

  // Покупатель находит товар в маркете и кладёт в корзину.
  const buyerContext = await browser.newContext()
  const buyerPage = await register(buyerContext, `Покупатель${Date.now()}`)

  await buyerPage.goto('/shop')
  await buyerPage.getByPlaceholder('Корм, игрушка, лежанка').fill(productTitle)
  await buyerPage.getByText(productTitle).click()

  await buyerPage.waitForURL(/\/shop\/products\//)
  await buyerPage.getByRole('button', { name: 'В корзину' }).click()
  await expect(buyerPage.getByText('Добавлено в корзину')).toBeVisible()

  // Оформление: пункт выдачи добавляет 200 ₽ к 1290 ₽.
  await buyerPage.goto('/shop/cart')
  await expect(buyerPage.getByText('1 290 ₽').first()).toBeVisible()
  await buyerPage.getByRole('button', { name: 'Оформить заказ' }).click()

  await buyerPage.waitForURL('/shop/checkout')
  await buyerPage.getByRole('radio', { name: 'СДЭК до пункта выдачи' }).click()
  await buyerPage.getByLabel('Адрес').fill('Москва, Тверская 1')
  await expect(buyerPage.getByText('1 490 ₽')).toBeVisible()

  await buyerPage.getByRole('button', { name: 'Перейти к оплате' }).click()
  await buyerPage.waitForURL('/shop/orders')

  const [order] = await fetchOrders(buyerPage)
  const orderId = order.id

  // Эмулируем успешный вебхук — платёж привязан к оформлению, а не к отдельному заказу.
  const webhookResponse = await buyerPage.request.post(
    `${API_URL}/api/v1/payments/webhooks/yookassa`,
    {
      data: {
        event: 'payment.succeeded',
        object: { id: `local-${order.checkout_id}:create`, status: 'succeeded' },
      },
    },
  )
  expect(webhookResponse.ok()).toBeTruthy()

  await buyerPage.goto(`/shop/orders/${orderId}`)
  await expect(buyerPage.getByText('Оплачен, деньги на удержании')).toBeVisible({ timeout: 15_000 })

  // Продавец отправляет заказ и подтверждает, покупатель тоже — заказ завершается.
  await sellerPage.goto(`/shop/orders/${orderId}`)
  await sellerPage.getByRole('button', { name: 'Передал в доставку' }).click()
  await expect(sellerPage.getByText('В доставке')).toBeVisible()

  await sellerPage.getByRole('button', { name: 'Подтвердить получение' }).click()
  await expect(sellerPage.getByText('Вы подтвердили, ждём вторую сторону')).toBeVisible()

  await buyerPage.reload()
  await buyerPage.getByRole('button', { name: 'Подтвердить получение' }).click()
  await expect(buyerPage.getByText('Завершён')).toBeVisible()

  await sellerContext.close()
  await buyerContext.close()
})

test('корзина с двумя продавцами разъезжается на два заказа под одной оплатой', async ({
  browser,
}) => {
  test.setTimeout(120_000)

  const firstTitle = `Игрушка${Date.now()}`
  const secondTitle = `Лежанка${Date.now()}`

  for (const [title, category] of [
    [firstTitle, 'Игрушки'],
    [secondTitle, 'Лежанки и домики'],
  ]) {
    const context = await browser.newContext()
    const page = await register(context, `Продавец${title}`)

    await page.goto('/shop/my-products')
    await page.getByRole('button', { name: 'Добавить товар' }).click()
    await page.getByRole('combobox', { name: 'Категория' }).click()
    await page.getByRole('option', { name: category }).click()
    await page.getByPlaceholder('Корм для щенков').fill(title)
    await page.getByPlaceholder('1290').fill('500')
    await page.getByRole('button', { name: 'Опубликовать' }).click()
    await expect(page.getByText(title)).toBeVisible()

    await context.close()
  }

  const buyerContext = await browser.newContext()
  const buyerPage = await register(buyerContext, `Покупатель${Date.now()}`)

  for (const title of [firstTitle, secondTitle]) {
    await buyerPage.goto('/shop')
    await buyerPage.getByPlaceholder('Корм, игрушка, лежанка').fill(title)
    await buyerPage.getByText(title).click()
    await buyerPage.waitForURL(/\/shop\/products\//)
    await buyerPage.getByRole('button', { name: 'В корзину' }).click()
    await expect(buyerPage.getByText('Добавлено в корзину')).toBeVisible()
  }

  // В корзине товары обоих продавцов, и она предупреждает о двух заказах.
  await buyerPage.goto('/shop/cart')
  await expect(buyerPage.getByText('Заказов будет 2')).toBeVisible()

  await buyerPage.getByRole('button', { name: 'Оформить заказ' }).click()
  await buyerPage.waitForURL('/shop/checkout')

  await buyerPage.getByRole('radio', { name: 'Самовывоз' }).click()
  await buyerPage.getByRole('button', { name: 'Перейти к оплате' }).click()
  await buyerPage.waitForURL('/shop/orders')

  // Два заказа под одним оформлением: самовывоз бесплатный, значит ровно сумма товаров.
  const orders = await fetchOrders(buyerPage)
  expect(orders).toHaveLength(2)
  expect(new Set(orders.map((order) => order.checkout_id)).size).toBe(1)

  const webhookResponse = await buyerPage.request.post(
    `${API_URL}/api/v1/payments/webhooks/yookassa`,
    {
      data: {
        event: 'payment.succeeded',
        object: { id: `local-${orders[0].checkout_id}:create`, status: 'succeeded' },
      },
    },
  )
  expect(webhookResponse.ok()).toBeTruthy()

  // Один платёж перевёл на эскроу оба заказа. Вебхук обрабатывается очередью, а список
  // заказов не опрашивает сервер — поэтому сначала дожидаемся статуса в API, потом
  // перезагружаем страницу и проверяем, что он виден.
  await expect
    .poll(async () => (await fetchOrders(buyerPage)).map((order) => order.status).sort(), {
      timeout: 15_000,
    })
    .toEqual(['paid_escrow', 'paid_escrow'])

  await buyerPage.reload()
  await expect(buyerPage.getByText('На удержании')).toHaveCount(2)

  await buyerContext.close()
})
