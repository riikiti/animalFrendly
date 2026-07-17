import { test, expect, type BrowserContext, type Page } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

async function registerWithPet(context: BrowserContext, petName: string): Promise<Page> {
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

/**
 * Лента кандидатов общая для всей dev-БД (см. тот же приём в matching-and-chat.spec.ts) —
 * ищем нужную анкету по имени, отклоняя остальные.
 */
async function swipeUntilFound(page: Page, targetName: string): Promise<void> {
  for (let attempt = 0; attempt < 30; attempt++) {
    const emptyState = page.getByText('Пока новых анкет рядом нет')
    if (await emptyState.isVisible().catch(() => false)) {
      throw new Error(`Candidate "${targetName}" not found before the feed ran out`)
    }

    const nameLocator = page.locator('h3').first()
    const currentName = await nameLocator.textContent()

    const swipeResponse = page.waitForResponse(
      (res) => res.url().includes('/swipes') && res.request().method() === 'POST',
    )

    if (currentName?.trim() === targetName) {
      await page.getByRole('button', { name: '♥' }).click()
      await swipeResponse

      return
    }

    await page.getByRole('button', { name: '✕' }).click()
    await swipeResponse
  }

  throw new Error(`Candidate "${targetName}" not found within the attempt limit`)
}

test('one-sided like shows up under sent/received, liking back from the likes tab creates a match', async ({
  browser,
}) => {
  test.setTimeout(60_000)

  const contextA = await browser.newContext()
  const contextB = await browser.newContext()

  const petNameA = `Барс${Date.now()}`
  const petNameB = `Луна${Date.now()}`

  const pageA = await registerWithPet(contextA, petNameA)
  const pageB = await registerWithPet(contextB, petNameB)

  // Лента у A была загружена ещё до регистрации B — обновляем, чтобы B попал в выдачу.
  await pageA.reload()
  await swipeUntilFound(pageA, petNameB)
  await expect(pageA.getByText('Это мэтч!')).not.toBeVisible()

  await pageA.goto('/likes')
  await pageA.getByRole('button', { name: 'Вы лайкнули' }).click()
  await expect(pageA.getByText(petNameB)).toBeVisible()
  await expect(pageA.getByText('Ждём ответа')).toBeVisible()

  // У B вкладка «Лайкнули вас» открыта по умолчанию.
  await pageB.goto('/likes')
  await expect(pageB.getByText(petNameA)).toBeVisible()

  const receivedCard = pageB.locator('div.rounded-2xl', { hasText: petNameA }).last()
  await receivedCard.getByRole('button', { name: 'Лайкнуть в ответ' }).click()
  await expect(pageB.getByText('Это мэтч!')).toBeVisible()

  await pageB.getByRole('button', { name: 'Написать сообщение' }).click()
  await pageB.waitForURL(/\/chat\/match\//)

  await pageB.getByPlaceholder('Сообщение…').fill('Привет!')
  await pageB.getByPlaceholder('Сообщение…').press('Enter')
  await expect(pageB.getByText('Привет!')).toBeVisible()
})

test('buyer writes to a marketplace seller directly from the seller page', async ({ browser }) => {
  test.setTimeout(60_000)

  const contextSeller = await browser.newContext()
  const contextBuyer = await browser.newContext()

  const sellerPetName = `Продавец-питомец${Date.now()}`
  const buyerPetName = `Покупатель-питомец${Date.now()}`
  const sellerName = `Олег${Date.now()}`
  const buyerName = `Игорь${Date.now()}`
  const listingPetName = `Кузя${Date.now()}`

  const sellerPage = await registerWithPet(contextSeller, sellerPetName)
  const buyerPage = await registerWithPet(contextBuyer, buyerPetName)

  await sellerPage.goto('/profile')
  await sellerPage.getByPlaceholder('Как вас называть').fill(sellerName)
  await sellerPage.getByRole('button', { name: 'Сохранить' }).click()
  await expect(sellerPage.getByText('Сохранено')).toBeVisible()

  await buyerPage.goto('/profile')
  await buyerPage.getByPlaceholder('Как вас называть').fill(buyerName)
  await buyerPage.getByRole('button', { name: 'Сохранить' }).click()
  await expect(buyerPage.getByText('Сохранено')).toBeVisible()

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
  await listingCard.getByRole('button', { name: sellerName }).click()
  await buyerPage.waitForURL(/\/marketplace\/sellers\/.+/)

  await buyerPage.getByRole('button', { name: 'Написать' }).click()
  await buyerPage.waitForURL(/\/chat\/direct\/.+/)

  await buyerPage.getByPlaceholder('Сообщение…').fill('Ещё продаётся?')
  await buyerPage.getByPlaceholder('Сообщение…').press('Enter')
  await expect(buyerPage.getByText('Ещё продаётся?')).toBeVisible()

  await sellerPage.goto('/chats')
  await expect(sellerPage.getByText(buyerName)).toBeVisible()
  await sellerPage.getByText(buyerName).click()
  await expect(sellerPage.getByText('Ещё продаётся?')).toBeVisible()
})
