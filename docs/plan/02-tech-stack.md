# Технологический стек

[← к оглавлению плана](../PLAN.md)

| Слой | Технология | Примечание |
|---|---|---|
| Backend | Laravel 11, PHP 8.3 | DDD, модульная структура — [`04-backend-structure.md`](./04-backend-structure.md) |
| БД | PostgreSQL 16 | JSONB для гибких атрибутов пород/тарифов |
| Кэш/очереди | Redis + Laravel Horizon | вебхуки, уведомления, авто-подтверждение сделок |
| Реалтайм | Laravel Reverb (WebSocket) + Echo | чат, live-обновления мэтчей |
| Поиск | Laravel Scout + Meilisearch | фильтры по породе/локации |
| Хранилище файлов | S3-совместимое (Yandex Object Storage/MinIO) | фото/видео питомцев, документы верификации |
| Платежи | ЮKassa API (официальный PHP SDK) | см. [`08-flow-marketplace-escrow.md`](./08-flow-marketplace-escrow.md) |
| Auth API | Laravel Sanctum (SPA + мобильные токены) | |
| Frontend (сайт) | Vue 3 + TypeScript, Vite, Pinia, Vue Router | Composition API, `<script setup>` |
| Стили | TailwindCSS + собственный UI-kit (design tokens) | mobile-first, дизайн зеркалится в приложении |
| Мобильное приложение | Kotlin Multiplatform (KMP): shared-модуль + Jetpack Compose (Android), задел под iOS | см. [`06-mobile-structure.md`](./06-mobile-structure.md) |
| Инфраструктура | Docker Compose (dev), CI — GitHub Actions | все окружения через `.env` |
| Тесты backend | Pest (Unit/Feature/Integration) | |
| Тесты frontend | Vitest + Vue Testing Library, Playwright (E2E) | |
| Тесты mobile | Kotlin Test/Kotest (shared), Compose UI Test (Android) | |

Полные правила по каждому слою — в `docs/rules/`.
