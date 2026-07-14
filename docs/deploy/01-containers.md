# Docker-образы

[← к оглавлению деплоя](../DEPLOY.md)

Один образ на приложение, разные команды запуска для разных ролей (см. таблицу сервисов в
[`../plan/12-infrastructure.md`](../plan/12-infrastructure.md)).

## `backend/Dockerfile` (Laravel)

Многостадийная сборка:

1. **`composer`-стадия** — `composer install --no-dev --optimize-autoloader` в кэшируемом
   слое (отдельно от копирования остального кода, чтобы `composer.lock` менялся редко).
2. **Финальная стадия** — PHP 8.5-FPM (Alpine) + nginx **или** Laravel Octane (FrankenPHP/
   Swoole) для `api`, если позже понадобится больше RPS на под; на старте достаточно
   классического PHP-FPM.
3. Команда контейнера параметризуется переменной `CONTAINER_ROLE`, у которой ровно 4
   допустимых значения — `entrypoint.sh` выбирает процесс:
   - `api` → `php-fpm` + nginx
   - `queue` → `php artisan horizon`
   - `scheduler` → `php artisan schedule:work` (Compose) / вызывается как `CronJob` в k8s
   - `websocket` → `php artisan reverb:start`
4. Никаких `.env`-значений в образе — только `.env.example` для справки, реальные значения
   приходят из Secret/env_file при запуске контейнера.

## `frontend/Dockerfile` (Vue3)

1. Стадия сборки — `node:20-alpine`, `npm ci && npm run build` (переменные окружения сборки
   — `VITE_*` — передаются как build args, не секреты, а публичные значения вроде URL API).
2. Финальная стадия — `nginx:alpine`, отдаёт статику из `dist/`, конфиг nginx делает SPA
   fallback на `index.html` и проксирует `/api`, `/ws` на бэкенд (в k8s — это делает
   Ingress, конфиг nginx внутри контейнера проксирования не содержит).

## Локальная разработка — `docker-compose.yml`

Корневой `docker-compose.yml` поднимает только зависимости, которые неудобно ставить
нативно — `postgres`, `redis`, `meilisearch`, `minio`. `backend`(Laravel/PHP) и
`frontend`(Vite dev-сервер) запускаются нативно на машине разработчика (`php artisan serve`,
`npm run dev`) — быстрее по обратной связи, чем через контейнер. Полный набор контейнеров
(`api`, `queue`, `scheduler`, `websocket`, `frontend`-сборка nginx) — тот же, что в проде,
собирается по `backend/Dockerfile`/`frontend/Dockerfile` и используется для прод-подобных
прогонов (staging, CI) — см. [`02-kubernetes.md`](./02-kubernetes.md).
