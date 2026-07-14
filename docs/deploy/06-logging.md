# Сбор и хранение логов

[← к оглавлению деплоя](../DEPLOY.md) · формат/что логировать — [`../plan/13-logging.md`](../plan/13-logging.md)

Приложение только пишет структурированный JSON в stdout/stderr (см.
[`../plan/13-logging.md`](../plan/13-logging.md)) — сбор, агрегация, хранение и поиск по
логам полностью на стороне инфраструктуры.

## Стек — Grafana Loki + Promtail

Выбор в пользу Loki (не ELK): существенно легче по ресурсам (важно для однонодового VPS
из [`03-vps-guide.md`](./03-vps-guide.md)), индексирует только метаданные/лейблы, а не
полнотекстовый индекс каждого лога, и одинаково просто разворачивается что в k8s, что в
Docker Compose — тот же принцип «одна и та же система что в кластере, что на VPS», что и
для остальной инфраструктуры (см. [`../plan/12-infrastructure.md`](../plan/12-infrastructure.md)).

## Kubernetes

- `loki` + `promtail` ставятся Helm-чартом (`grafana/loki-stack` или раздельные
  `grafana/loki` + `grafana/promtail`) в отдельный namespace `observability`.
- `promtail` — `DaemonSet`, читает stdout/stderr всех подов через container runtime,
  размечает лейблами `namespace`, `pod`, `container` — ручная настройка сборки логов на
  уровне приложения не нужна.
- `grafana` — отдельный `Deployment` там же, за Ingress с обязательной авторизацией
  (не публичный дашборд), источник данных — `loki`.

## Голый VPS (оба варианта из `03-vps-guide.md`)

- Вариант A (k3s) — тот же Helm-чарт, что и в кластере, без дополнительной настройки.
- Вариант B (Docker Compose) — добавить в `docker-compose.prod.yml` сервисы `loki`,
  `promtail` (с монтированием `/var/lib/docker/containers` для чтения json-file логов
  Docker) и `grafana`. Требует дополнительно ~500 МБ–1 ГБ RAM — на очень маленьком VPS
  (2 vCPU/4 GB из минимальных требований гайда) может быть тесно вместе с
  `postgres`/`redis`/`meilisearch`; в этом случае — вынести Grafana/Loki на отдельный
  маленький VPS или использовать облачный Grafana Cloud (бесплатный тир) вместо
  самостоятельного хостинга агрегатора, не меняя ничего в приложении (оно просто пишет в
  stdout независимо от того, кто логи собирает).

## Хранение и алерты

- Retention логов — 14–30 дней (совпадает с горизонтом бэкапов БД, см.
  [`04-operations.md`](./04-operations.md)); это оперативная история для отладки, не
  замена `audit_logs` в БД (тот — постоянный, см.
  [`../plan/13-logging.md`](../plan/13-logging.md)).
- Grafana Alerting — правила поверх логов/метрик по событиям из
  [`04-operations.md`](./04-operations.md): рост очереди Horizon быстрее обработки, рост
  `payouts.status = failed`, всплеск `level >= error` по каналу `marketplace`/`payment`.
- Доступ к Grafana — только для команды, за аутентификацией (Basic Auth на Ingress как
  минимум на старте, SSO — по мере роста команды).
