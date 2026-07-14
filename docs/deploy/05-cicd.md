# CI/CD (GitHub Actions)

[← к оглавлению деплоя](../DEPLOY.md)

> **Когда делать:** этот пайплайн настраивается **после того, как написан основной код**
> модулей из [`../plan/00-overview.md`](../plan/00-overview.md) (после фаз 1–6) — то есть
> когда есть что тестировать и куда катить. До этого момента тесты и линтеры (см.
> [`../rules/05-testing.md`](../rules/05-testing.md)) запускаются локально/в PR-проверках
> базового уровня, полноценный деплой-пайплайн не строится ради ещё пустых модулей.
> Инфраструктура (k8s/Compose, [`02-kubernetes.md`](./02-kubernetes.md),
> [`03-vps-guide.md`](./03-vps-guide.md)) может быть поднята раньше — CI/CD её просто
> начинает использовать, когда приходит время.

## Состав workflow'ов (`.github/workflows/`)

| Файл | Триггер | Что делает |
|---|---|---|
| `backend-ci.yml` | `pull_request` (пути `backend/**`) | Pint (стиль), Larastan (статанализ), Pest (Unit/Feature/Integration) |
| `frontend-ci.yml` | `pull_request` (пути `frontend/**`) | ESLint+Prettier, `vue-tsc` (типы), Vitest, Playwright (на критичные флоу) |
| `mobile-ci.yml` | `pull_request` (пути `mobile/**`) | ktlint/detekt, сборка `shared` под JVM, Kotlin Test/Kotest, сборка `androidApp` (debug APK) |
| `build-and-push.yml` | `push` в `main` (после успешных CI выше) | сборка `backend`/`frontend` Docker-образов, тег = git SHA + `latest`, push в registry |
| `deploy-staging.yml` | `push` в `main`, после `build-and-push` | `helm upgrade` в namespace `animalfriendly-staging` (или `docker compose` по SSH — см. Вариант B) — автоматически, без подтверждения |
| `deploy-production.yml` | вручную (`workflow_dispatch`) или по git tag `v*` | `helm upgrade` в `animalfriendly-production`, через GitHub Environment с обязательным approval (см. ниже) |

## Правила

- PR не мержится, пока не зелёный весь применимый CI (backend/frontend/mobile — только
  те, что затронуты изменёнными путями).
- Деплой в `production` — только через **GitHub Environment `production`** с настроенным
  required reviewer (ручное подтверждение перед раскаткой), деплой в `staging` — без
  подтверждения, на каждый мердж в `main`.
- Секреты пайплайна (kubeconfig/SSH-ключ, registry credentials, боевые ключи ЮKassa для
  smoke-теста) — только в GitHub Actions Secrets на уровне репозитория/окружения, никогда
  в самом workflow-файле.
- `build-and-push` использует тот же `backend/Dockerfile`/`frontend/Dockerfile`, что
  описаны в [`01-containers.md`](./01-containers.md) — нет отдельной «CI-версии» сборки.
- Миграции при деплое запускаются как отдельный шаг/Job (см. правило в
  [`04-operations.md`](./04-operations.md)), а не автоматически при старте пода.
- Откат — по инструкции [`04-operations.md`](./04-operations.md) (`helm rollback` /
  `git checkout` + пересборка), CI/CD не автоматизирует откат на старте — ручной запуск
  `deploy-production.yml` с предыдущим тегом достаточно.
