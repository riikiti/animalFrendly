# Тестирование

[← к оглавлению правил](../RULES.md)

Пирамида тестов — одинаковый принцип на всех трёх платформах: много быстрых чистых
юнит-тестов домена, меньше интеграционных, минимум дорогих E2E только на критичных флоу.

## Backend (Pest)

| Уровень | Что покрывает | Где |
|---|---|---|
| Unit/Domain | Чистая бизнес-логика (state machine сделки, расчёт комиссии, лимиты подписки) — без БД и HTTP | `tests/Unit/Modules/{Module}/Domain` |
| Unit/Application | Use case'ы с замоканными репозиториями | `tests/Unit/Modules/{Module}/Application` |
| Feature | HTTP-эндпоинты с реальной тестовой БД (sqlite in-memory или отдельная pgsql testing) | `tests/Feature/Modules/{Module}/Http` |
| Integration | Внешние адаптеры — ЮKassa (sandbox/фикстуры), FCM, S3 | `tests/Integration/Modules/{Module}` |

Обязательные сценарии для `Payment`/`Marketplace` — см.
[`04-payments-escrow.md`](./04-payments-escrow.md) (последний пункт).
Минимальный порог покрытия для Domain+Application — 80%, PR ниже порога не мержится.

## Frontend (Vue3)

- Vitest — composables, Pinia-сторы.
- Vue Testing Library — ключевые компоненты (`PetCard`, `MatchModal`, `DealStatusTimeline`,
  `PaywallSheet`).
- Playwright E2E — обязателен для: онбординг, свайп→мэтч→чат, покупка листинга→оплата→
  экран статуса эскроу, оформление/отмена подписки.

## Mobile (KMP)

- `commonMain` — Kotlin Test/Kotest на use case'ы и репозитории (с фейковыми
  data-источниками), гоняются на JVM без эмулятора — быстрые.
- Android — Compose UI Test на критичные экраны (свайп-жест, экран оплаты, чат).

## CI

Все три пайплайна (backend/frontend/mobile) гоняются на каждый PR; линт/статический анализ
— обязательная стадия перед тестами. Смёрженный в `main` код обязан проходить полный набор.
