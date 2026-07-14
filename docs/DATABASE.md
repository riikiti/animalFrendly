# AnimalFriendly — структура БД (оглавление)

> PostgreSQL 16. Общие правила именования, типов и денег — сначала прочитать
> [`database/00-conventions.md`](./database/00-conventions.md), они действуют для всех таблиц ниже.

- [`database/00-conventions.md`](./database/00-conventions.md) — соглашения (id, деньги, время, аудит, миграции)
- [`database/01-identity-profile.md`](./database/01-identity-profile.md) — `users`, `roles`, `user_profiles`, `pets`, `pet_photos`
- [`database/02-catalog.md`](./database/02-catalog.md) — `species`, `breeds`
- [`database/03-matching-chat.md`](./database/03-matching-chat.md) — `swipes`, `matches`, `conversations`, `messages`
- [`database/04-shelter.md`](./database/04-shelter.md) — `shelters`, `shelter_animals`, `adoption_requests`
- [`database/05-marketplace-payment.md`](./database/05-marketplace-payment.md) — `listings`, `orders`, `payments`, `payouts`, `disputes` (эскроу)
- [`database/06-subscription.md`](./database/06-subscription.md) — `subscription_plans`, `subscriptions`, `feature_usage`
- [`database/07-notification-media-moderation.md`](./database/07-notification-media-moderation.md) — `media`, `notifications`, `reports`, `reviews`, `audit_logs`

Каждая таблица принадлежит ровно одному модулю бэкенда — соответствие модуль↔таблицы см.
в [`plan/01-modules.md`](./plan/01-modules.md) и [`plan/04-backend-structure.md`](./plan/04-backend-structure.md).
