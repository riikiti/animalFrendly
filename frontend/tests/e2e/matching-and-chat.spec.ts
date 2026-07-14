import { test, expect, type BrowserContext, type Page } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

async function registerWithPet(context: BrowserContext, petName: string): Promise<Page> {
  const page = await context.newPage()
  const phone = randomPhone()

  await page.goto('/register')
  await page.getByText('Я — владелец').click()
  await page.getByPlaceholder('+7 926 123-45-67').fill(phone)

  const passwordInputs = page.locator('input[type="password"]')
  await passwordInputs.nth(0).fill('correct-password')
  await passwordInputs.nth(1).fill('correct-password')
  await page.locator('input[type="checkbox"]').check()
  await page.getByRole('button', { name: 'Продолжить' }).click()

  await page.waitForURL('/pets/new')
  await page.getByPlaceholder('Рекс').fill(petName)
  await page.getByRole('button', { name: 'Создать анкету' }).click()
  await page.waitForURL('/')

  return page
}

/**
 * Лента кандидатов — общая для всей dev-БД (тесты не изолированы миграциями, в отличие
 * от backend Pest-тестов), поэтому ищем нужную анкету по имени, отклоняя остальные, а не
 * полагаемся на то, что она придёт первой.
 */
async function swipeUntilFound(page: Page, targetName: string): Promise<void> {
  for (let attempt = 0; attempt < 30; attempt++) {
    const emptyState = page.getByText('Пока новых анкет рядом нет')
    if (await emptyState.isVisible().catch(() => false)) {
      throw new Error(`Candidate "${targetName}" not found before the feed ran out`)
    }

    const nameLocator = page.locator('h3').first()
    const currentName = await nameLocator.textContent()

    // Ждём завершения запроса свайпа, прежде чем читать следующую карточку — иначе
    // список кандидатов на странице ещё не успел обновиться (см. SwipePage.vue::onSwipe)
    // и цикл кликает по той же самой анкете дважды.
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

test('two pet owners mutually like each other, get matched, and chat', async ({ browser }) => {
  const contextA = await browser.newContext()
  const contextB = await browser.newContext()

  const petNameA = `Барс${Date.now()}`
  const petNameB = `Луна${Date.now()}`

  const pageA = await registerWithPet(contextA, petNameA)
  const pageB = await registerWithPet(contextB, petNameB)

  // Лента кандидатов у A была загружена при первом заходе на '/', ещё до регистрации B —
  // обновляем, чтобы свежесозданная анкета B попала в выдачу.
  await pageA.reload()

  // A свайпает мимо B — ещё не мэтч, взаимности пока нет.
  await swipeUntilFound(pageA, petNameB)
  await expect(pageA.getByText('Это мэтч!')).not.toBeVisible()

  // B лайкает A в ответ — взаимный лайк создаёт мэтч.
  await swipeUntilFound(pageB, petNameA)
  await expect(pageB.getByText('Это мэтч!')).toBeVisible()

  await pageB.getByRole('button', { name: 'Написать сообщение' }).click()
  await pageB.waitForURL(/\/chat\//)

  await pageB.getByPlaceholder('Сообщение…').fill('Привет! Погуляем с нашими?')
  await pageB.getByPlaceholder('Сообщение…').press('Enter')
  await expect(pageB.getByText('Привет! Погуляем с нашими?')).toBeVisible()

  // A ещё не переходил в чат — открывает его отдельно, по тому же мэтчу.
  const matchUrl = pageB.url()
  await pageA.goto(matchUrl)
  await expect(pageA.getByText('Привет! Погуляем с нашими?')).toBeVisible()

  await pageA.getByPlaceholder('Сообщение…').fill('С радостью!')
  await pageA.getByPlaceholder('Сообщение…').press('Enter')
  await expect(pageA.getByText('С радостью!')).toBeVisible()

  // Сообщение от A должно долететь до B через поллинг (см. ChatPage.vue).
  await expect(pageB.getByText('С радостью!')).toBeVisible({ timeout: 5000 })
})
