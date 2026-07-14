# Frontend (Vue 3)

[← к оглавлению правил](../RULES.md) · структура — [`../plan/05-frontend-structure.md`](../plan/05-frontend-structure.md)

- **TypeScript strict mode**, `any` запрещён без явного комментария-обоснования.
- Только **Composition API** с `<script setup lang="ts">`, Options API не используется.
- **Feature-Sliced Design**: импорт только «сверху вниз»
  (`pages → widgets → features → entities → shared`). Обратный импорт — ошибка ревью.
- **Состояние:** Pinia store на каждую entity/feature, без одного глобального стора на
  всё приложение.
- **API-слой:** все HTTP-запросы — только через `shared/api` (типизированный клиент),
  компоненты и стораны не делают `fetch`/`axios` напрямую. Типы ответов синхронизированы
  с backend API Resources.
- **Стили:** TailwindCSS + UI-kit из `shared/ui`; дизайн-токены — единственный источник
  правды по цвету/типографике/отступам (см. [`00-general.md`](./00-general.md)).
- **Mobile-first:** вёрстка проектируется от мобильного экрана вверх — мобильный вид сайта
  должен быть визуально идентичен Android-приложению (см. макет,
  [`../mockups/mockup.html`](../mockups/mockup.html)).
- **Пэйволл:** проверка доступа к платной фиче — только через единый composable
  `useSubscription()` / `useFeatureGate()`, разбросанных проверок тарифа по компонентам
  быть не должно.
- Доступность: интерактивные элементы — семантические теги, `aria-*` где уместно,
  управление с клавиатуры для модалок (чат, оплата).
- Линт/формат: ESLint + Prettier, обязательны в CI.

## Тесты

- Vitest — composables и stores (unit).
- Vue Testing Library — компоненты (что рендерится и как реагирует на события).
- Playwright — E2E для критичных флоу: свайп→мэтч, покупка листинга→оплата→статус эскроу,
  оформление подписки. Подробнее — [`05-testing.md`](./05-testing.md).
