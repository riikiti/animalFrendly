import { test, expect, type BrowserContext, type Page } from '@playwright/test'

function randomPhone(): string {
  return `+7926${Math.floor(1000000 + Math.random() * 8999999)}`
}

// Минимальный валидный 1×1 PNG — Laravel-правило "image" проверяет содержимое файла
// (getimagesize), поэтому нельзя просто отправить произвольные байты.
const onePixelPng = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
  'base64',
)

async function uploadPhotoAndWait(page: Page, filename: string): Promise<void> {
  const response = page.waitForResponse(
    (res) => res.url().includes('/photos') && res.request().method() === 'POST',
  )
  await page
    .locator('input[type="file"]')
    .last()
    .setInputFiles({ name: filename, mimeType: 'image/png', buffer: onePixelPng })
  await response
}

async function registerWithPet(
  context: BrowserContext,
  petName: string,
  withPhoto: boolean,
): Promise<Page> {
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

  if (withPhoto) {
    await uploadPhotoAndWait(page, 'cat.png')
  }

  await page.getByRole('button', { name: 'Готово' }).click()
  await page.waitForURL('/')

  return page
}

/**
 * См. тот же приём в matching-and-chat.spec.ts — лента общая для всей dev-БД.
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

test('pet photo is visible in the feed, and a match triggers an in-app notification', async ({
  browser,
}) => {
  const contextA = await browser.newContext()
  const contextB = await browser.newContext()

  const petNameA = `Барс${Date.now()}`
  const petNameB = `Луна${Date.now()}`

  const pageA = await registerWithPet(contextA, petNameA, false)
  const pageB = await registerWithPet(contextB, petNameB, true)

  await pageA.reload()

  // Ищем анкету B на карточке подбора у A и проверяем, что её фото загрузилось.
  for (let attempt = 0; attempt < 30; attempt++) {
    const nameLocator = pageA.locator('h3').first()
    const currentName = await nameLocator.textContent()

    if (currentName?.trim() === petNameB) break

    const swipeResponse = pageA.waitForResponse(
      (res) => res.url().includes('/swipes') && res.request().method() === 'POST',
    )
    await pageA.getByRole('button', { name: '✕' }).click()
    await swipeResponse
  }

  await expect(pageA.locator('img[src*="/storage/"]').first()).toBeVisible()

  // A лайкает B — ещё не мэтч.
  await swipeUntilFound(pageA, petNameB)

  // B лайкает A в ответ — взаимный лайк, мэтч создаётся для обоих.
  await swipeUntilFound(pageB, petNameA)
  await expect(pageB.getByText('Это мэтч!')).toBeVisible()

  // У A должен появиться бейдж непрочитанного уведомления о новом мэтче.
  await pageA.reload()
  await expect(pageA.getByText('1', { exact: true })).toBeVisible({ timeout: 10_000 })

  await pageA.getByText('🔔').click()
  await pageA.waitForURL('/notifications')
  await expect(pageA.getByText('У вас новый мэтч!')).toBeVisible()

  await pageA.getByText('У вас новый мэтч!').click()
  await expect(pageA.getByText('Прочитать всё')).not.toBeVisible()
})

test('manages a photo gallery: add several photos, change the cover, remove one', async ({
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

  await page.waitForURL('/pets/new')
  await page.getByPlaceholder('Рекс').fill(`Галерейный${Date.now()}`)
  await page.getByRole('button', { name: 'Создать анкету' }).click()

  await uploadPhotoAndWait(page, 'first.png')
  await expect(page.getByText('Обложка')).toBeVisible()

  await uploadPhotoAndWait(page, 'second.png')
  await expect(page.getByText('Сделать обложкой')).toBeVisible()

  await page.getByText('Сделать обложкой').click()
  await expect(page.getByText('Обложка')).toBeVisible()
  await expect(page.getByText('Сделать обложкой')).toBeVisible()

  await page.locator('button', { hasText: '✕' }).first().click()
  await expect(page.getByText('Сделать обложкой')).not.toBeVisible()

  await page.getByRole('button', { name: 'Готово' }).click()
  await page.waitForURL('/')
})
