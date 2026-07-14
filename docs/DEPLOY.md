# AnimalFriendly — деплой (оглавление)

> Приоритет оркестрации — Kubernetes, Docker Compose — для локальной разработки и как
> fallback для маленьких VPS-инсталляций. Обоснование и список сервисов — в
> [`plan/12-infrastructure.md`](./plan/12-infrastructure.md).

- [`deploy/01-containers.md`](./deploy/01-containers.md) — Docker-образы backend/frontend, `docker-compose.yml` для разработки
- [`deploy/02-kubernetes.md`](./deploy/02-kubernetes.md) — структура Helm-chart/манифестов, workloads, секреты, ingress
- [`deploy/03-vps-guide.md`](./deploy/03-vps-guide.md) — пошаговый гайд разворачивания на голом VPS (k3s — приоритетный вариант, Docker Compose — упрощённый)
- [`deploy/04-operations.md`](./deploy/04-operations.md) — бэкапы, миграции в проде, мониторинг, обновления и откат
- [`deploy/05-cicd.md`](./deploy/05-cicd.md) — GitHub Actions: тесты, линтеры, сборка образов, деплой (настраивается **после** основного кода)
