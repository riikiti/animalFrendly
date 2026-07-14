# Общая архитектура

[← к оглавлению плана](../PLAN.md)

```mermaid
flowchart LR
    subgraph Clients
        Web[Vue3 SPA / mobile-web]
        Android[Android app - KMP + Compose]
    end

    subgraph Backend["Laravel API (DDD, модули)"]
        API[REST API v1 + WebSocket]
    end

    subgraph Infra
        PG[(PostgreSQL)]
        Redis[(Redis)]
        Meili[(Meilisearch)]
        S3[(Object Storage)]
    end

    subgraph External["Внешние сервисы (ключи из .env)"]
        YooKassa[ЮKassa]
        FCM[Firebase Cloud Messaging]
        Mail[SMTP/Email провайдер]
    end

    Web --> API
    Android --> API
    API --> PG
    API --> Redis
    API --> Meili
    API --> S3
    API --> YooKassa
    API --> FCM
    API --> Mail
```

Все внешние адаптеры (ЮKassa, FCM, S3, SMTP) реализуются как порты/интерфейсы в
`Domain`/`Application`-слое соответствующего модуля и адаптеры в `Infrastructure` —
подмена провайдера не должна требовать правок бизнес-логики.

Список модулей (bounded contexts), из которых состоит `Backend`, — в
[`01-modules.md`](./01-modules.md). Правило межмодульного взаимодействия (только через
события/явные контракты, без прямых импортов чужих Eloquent-моделей) обязательно к
соблюдению и на бэкенде, и логически отражается в разделении `entities/features` на
фронтенде.
