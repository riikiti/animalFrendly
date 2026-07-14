# Мобильное приложение: Kotlin Multiplatform

[← к оглавлению плана](../PLAN.md)

Выбор: **Kotlin Multiplatform (KMP)**. Сейчас — Android-приложение на Jetpack Compose,
но домен, сеть, локальное хранилище и use-case'ы находятся в `shared`-модуле и не зависят
от платформы — при разработке iOS-версии переиспользуются напрямую (Compose Multiplatform
или SwiftUI поверх того же `shared`).

```
mobile/
├── shared/
│   └── src/
│       ├── commonMain/kotlin/com/animalfriendly/
│       │   ├── domain/          # entities, use cases, repository-интерфейсы (чистый Kotlin)
│       │   ├── data/
│       │   │   ├── network/     # Ktor client, DTO, соответствуют API v1 backend
│       │   │   └── local/       # SQLDelight (мультиплатформенный, не Room — задел под iOS)
│       │   └── di/              # Koin-модули (мультиплатформенный DI)
│       ├── androidMain/kotlin/  # платформенные реализации (напр. FCM push-приёмник)
│       └── iosMain/kotlin/      # заглушки/платформенные реализации под iOS (заполняются позже)
├── androidApp/
│   └── src/main/kotlin/com/animalfriendly/android/
│       ├── ui/                  # экраны на Jetpack Compose, зеркалят src/pages фронта:
│       │                        # SwipeScreen, ChatScreen, ShelterScreen, MarketplaceScreen,
│       │                        # DealScreen, SubscriptionScreen, ProfileScreen
│       ├── ui/theme/             # те же design tokens, что и в shared/ui фронта
│       └── navigation/
├── iosApp/                       # пустой Xcode-проект-заглушка (наполняется в фазе iOS)
└── gradle/
```

Правило соответствия: **каждый экран/флоу в мобильном приложении обязан повторять
мобильную версию сайта** (тот же порядок шагов, те же тексты, тот же дизайн). Расхождение
допустимо только если сначала обновлён план (см. [`05-frontend-structure.md`](./05-frontend-structure.md)
и [`../mockups/mockup.html`](../mockups/mockup.html)).

Правила кода для этого слоя — [`../rules/03-mobile.md`](../rules/03-mobile.md).
