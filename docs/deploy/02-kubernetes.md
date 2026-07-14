# Kubernetes: структура манифестов

[← к оглавлению деплоя](../DEPLOY.md)

Приоритетная платформа — см. [`../plan/12-infrastructure.md`](../plan/12-infrastructure.md).
Организовано как Helm-chart (проще версионировать значения per-окружение), но применимо и
как обычные YAML + `kustomize`, если Helm не нужен.

```
deploy/k8s/
├── charts/animalfriendly/
│   ├── Chart.yaml
│   ├── values.yaml               # дефолты
│   ├── values-staging.yaml
│   ├── values-production.yaml
│   └── templates/
│       ├── api-deployment.yaml
│       ├── queue-deployment.yaml
│       ├── websocket-deployment.yaml
│       ├── scheduler-cronjob.yaml
│       ├── frontend-deployment.yaml
│       ├── migrate-job.yaml       # Helm hook: pre-upgrade/pre-install
│       ├── ingress.yaml
│       ├── hpa.yaml                # HorizontalPodAutoscaler для api
│       ├── secrets.yaml            # ссылки на внешние Secret (не значения!)
│       ├── configmap.yaml          # не-секретный конфиг (APP_ENV, лог-уровень)
│       └── postgres-statefulset.yaml   # только если БД не managed-сервис облака
└── README.md
```

## Namespace и окружения

- `animalfriendly-staging`, `animalfriendly-production` — отдельные namespace, отдельные
  `values-*.yaml`, отдельные Secret.
- Один и тот же Helm-chart разворачивает оба окружения — различаются только значения
  (реплики, домены, ключи ЮKassa — боевые vs тестовые).

## Workloads

| Ресурс | Тип | Примечание |
|---|---|---|
| `api` | `Deployment` + `HorizontalPodAutoscaler` | readiness/liveness на `/api/health`, `PodDisruptionBudget` min 1 |
| `queue-worker` | `Deployment` (Horizon) | реплики растут при росте очереди вебхуков/уведомлений |
| `websocket` | `Deployment` | Ingress с `sticky session` (cookie-based) для WebSocket-соединений |
| `scheduler` | `CronJob` (`*/1 * * * *` → `php artisan schedule:run`) | не `Deployment` — не нужен постоянно работающий процесс |
| `migrate` | `Job`, Helm-хук `pre-upgrade,pre-install` | одноразовый запуск миграций перед раскаткой новой версии, см. правило в [`../plan/12-infrastructure.md`](../plan/12-infrastructure.md) |
| `frontend` | `Deployment` | статика за nginx, отдельный от `api` под |
| `postgres` / `redis` | managed-сервис облака (рекомендуется) либо `StatefulSet` + `PersistentVolumeClaim` | на голом VPS — `StatefulSet`, см. [`03-vps-guide.md`](./03-vps-guide.md) |

## Секреты и конфиг

- `Secret` создаётся **вне** Helm-релиза (`kubectl create secret` или внешний
  secret-manager типа `sealed-secrets`/`external-secrets`) — в git не попадает ничего,
  кроме имён ключей, которые ожидает `Deployment`.
- Обязательные секреты: `YOOKASSA_SHOP_ID`, `YOOKASSA_SECRET_KEY`, `YOOKASSA_WEBHOOK_SECRET`,
  `DB_PASSWORD`, `REDIS_PASSWORD`, `FCM_CREDENTIALS`, `AWS_SECRET_ACCESS_KEY`,
  `APP_KEY` — полный список переменных см. [`../plan/10-integrations.md`](../plan/10-integrations.md).
- Не-секретный конфиг (`APP_ENV`, `LOG_LEVEL`, публичные URL) — через `ConfigMap`.

## Ingress и TLS

- Один `Ingress` на namespace: `/api`, `/ws` → бэкенд-сервисы, `/` → `frontend`.
- TLS — `cert-manager` + `ClusterIssuer` (Let's Encrypt), автопродление сертификатов.

## Автоскейлинг и отказоустойчивость

- `HorizontalPodAutoscaler` на `api` по CPU (базовый вариант) или по RPS через
  кастомные метрики (продвинутый вариант, не на старте).
- `queue-worker` — минимум 1 реплика всегда (иначе обработка вебхуков ЮKassa и
  авто-подтверждение сделок остановится); в проде — 2 для отказоустойчивости.
- Все `Deployment`, кроме `websocket`, — полностью stateless, спокойно переживают
  перезапуск/rolling update без потери данных (state — только в Postgres/Redis).
