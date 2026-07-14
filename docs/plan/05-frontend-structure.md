# Frontend (Vue 3): структура папок

[← к оглавлению плана](../PLAN.md)

Методология — **Feature-Sliced Design (FSD)**, импорт возможен только «сверху вниз»
(`pages` может импортировать `widgets`, но не наоборот).

```
frontend/
├── src/
│   ├── app/                # инициализация: router, pinia, plugins, layout
│   ├── pages/               # страницы-роуты: /swipe, /chat/:id, /shelter/:id,
│   │                        # /marketplace, /marketplace/listing/:id, /deal/:id,
│   │                        # /subscription, /profile, /pet/:id/edit ...
│   ├── widgets/             # составные блоки: PetCard, MatchModal, ChatWindow,
│   │                        # ListingCard, DealStatusTimeline, PaywallSheet
│   ├── features/            # юзкейсы: swipe-pet, send-message, create-adoption-request,
│   │                        # purchase-listing, confirm-deal, buy-subscription, auth-by-phone
│   ├── entities/            # доменные сущности фронта: user, pet, shelter, listing, deal,
│   │                        # subscription-plan — модели + их API-запросы
│   ├── shared/
│   │   ├── ui/              # UI-kit (design tokens, кнопки, инпуты, карточки) — единый
│   │   │                    # источник дизайна, который зеркалит мобильное приложение
│   │   ├── api/              # типизированный http-клиент (axios/ofetch + типы из backend Resources)
│   │   ├── composables/
│   │   └── lib/
│   └── processes/            # сквозные флоу: онбординг, оформление покупки с оплатой
├── tests/
│   ├── unit/                 # Vitest — composables, stores
│   ├── component/            # Vue Testing Library
│   └── e2e/                  # Playwright — свайп→мэтч, покупка→эскроу, оформление подписки
└── .env.example
```

Дизайн-токены (`shared/ui/tokens`) — единственный источник правды по цветам/отступам/
типографике; те же токены передаются в мобильное приложение
(см. [`06-mobile-structure.md`](./06-mobile-structure.md)), чтобы UI совпадал.

Правила кода для этого слоя — [`../rules/02-frontend.md`](../rules/02-frontend.md).
Референс экранов — [`../mockups/mockup.html`](../mockups/mockup.html) (опубликован как Artifact).
