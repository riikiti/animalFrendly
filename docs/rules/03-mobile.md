# Mobile (Kotlin Multiplatform)

[← к оглавлению правил](../RULES.md) · структура — [`../plan/06-mobile-structure.md`](../plan/06-mobile-structure.md)

- **`commonMain` — чистый Kotlin**, никаких Android/iOS-specific импортов (`android.*`,
  `androidx.*` запрещены в `commonMain`). Платформенный код — только в `androidMain`/`iosMain`.
- **Архитектура экрана:** MVI-подобный подход — состояние и обработка intent'ов живут в
  shared-слое (ViewModel/StateHolder в `commonMain` где возможно), Compose-экран в
  `androidApp` — «глупый», только рендерит state и шлёт события наверх.
- **Сеть:** Ktor client в `shared/data/network`, DTO соответствуют контракту backend API v1
  (тот же формат, что и `shared/api` на фронтенде — расхождение форматов не допускается).
- **Локальное хранилище:** SQLDelight, не Room — сохраняет мультиплатформенность ради
  будущего iOS (см. решение в [`../plan/06-mobile-structure.md`](../plan/06-mobile-structure.md)).
- **DI:** Koin (мультиплатформенный), модули регистрируются в `shared/di`.
- **UI:** Jetpack Compose, тема (`ui/theme`) использует те же значения, что и
  `frontend/src/shared/ui/tokens` — см. [`00-general.md`](./00-general.md).
- **Паритет с сайтом:** каждый экран мобильного приложения обязан повторять мобильную
  версию сайта (тот же порядок шагов, тексты, состояние). Если продуктовая логика для
  мобилки должна отличаться — сначала правится план
  ([`../plan/06-mobile-structure.md`](../plan/06-mobile-structure.md)), потом код.
- **Секреты:** базовый URL API и публичные ключи — через `BuildConfig`, значения берутся
  из `local.properties`/CI secrets, не коммитятся.

## Тесты

- `commonMain`-логика — Kotlin Test/Kotest, без Android-зависимостей (быстрые, гоняются на
  JVM).
- UI на Android — Compose UI Test для критичных экранов (свайп, оплата, чат).
  Подробнее — [`05-testing.md`](./05-testing.md).
