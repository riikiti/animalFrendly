# AnimalFriendly — план проекта (оглавление)

> **Правило:** любое изменение скоупа, доменной модели, стека или структуры папок сначала
> вносится в соответствующий файл плана ниже (или в `DATABASE.md` / `RULES.md`), и только
> потом — в код. Каждый файл — небольшой и посвящён одной теме, не открывайте лишние файлы,
> если работаете над конкретным разделом.

## Продукт

- [`plan/00-overview.md`](./plan/00-overview.md) — концепция, роли пользователей, роадмап, открытые вопросы
- [`plan/01-modules.md`](./plan/01-modules.md) — функциональные модули (bounded contexts)
- [`plan/02-tech-stack.md`](./plan/02-tech-stack.md) — технологический стек

## Архитектура

- [`plan/03-architecture.md`](./plan/03-architecture.md) — общая схема системы
- [`plan/04-backend-structure.md`](./plan/04-backend-structure.md) — Laravel/DDD, структура папок бэкенда
- [`plan/05-frontend-structure.md`](./plan/05-frontend-structure.md) — Vue3/FSD, структура папок фронтенда
- [`plan/06-mobile-structure.md`](./plan/06-mobile-structure.md) — Kotlin Multiplatform, структура мобильного приложения

## Бизнес-флоу

- [`plan/07-flow-matching-shelter.md`](./plan/07-flow-matching-shelter.md) — свайп/мэтч, усыновление через приют
- [`plan/08-flow-marketplace-escrow.md`](./plan/08-flow-marketplace-escrow.md) — маркетплейс, эскроу-сделка, комиссия 5%
- [`plan/09-flow-subscriptions.md`](./plan/09-flow-subscriptions.md) — тарифы/подписки, paywall

## Прочее

- [`plan/10-integrations.md`](./plan/10-integrations.md) — внешние сервисы и переменные `.env`
- [`plan/11-non-functional.md`](./plan/11-non-functional.md) — безопасность, 152-ФЗ, масштабируемость
- [`plan/12-infrastructure.md`](./plan/12-infrastructure.md) — контейнеризация, приоритет Kubernetes, единый бэкенд/БД для сайта и мобилки

## Связанные документы

- [`DATABASE.md`](./DATABASE.md) — структура БД (по модулям, отдельные файлы)
- [`RULES.md`](./RULES.md) — правила написания кода (по слоям, отдельные файлы)
- [`DEPLOY.md`](./DEPLOY.md) — деплой: контейнеры, Kubernetes, гайд по разворачиванию на голом VPS
- [`mockups/mockup.html`](./mockups/mockup.html) — макет ключевых экранов (также опубликован как Artifact)
