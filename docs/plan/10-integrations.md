# Внешние интеграции (обязательно через `.env`)

[← к оглавлению плана](../PLAN.md)

| Сервис | Назначение | Переменные (пример) |
|---|---|---|
| ЮKassa | Платежи, эскроу, подписки, выплаты | `YOOKASSA_SHOP_ID`, `YOOKASSA_SECRET_KEY`, `YOOKASSA_WEBHOOK_SECRET` |
| Firebase Cloud Messaging | Push-уведомления Android | `FCM_PROJECT_ID`, `FCM_CREDENTIALS_PATH` |
| S3-совместимое хранилище | Фото/видео, документы верификации | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT` |
| SMTP/Email провайдер | Письма (подтверждение, чек, уведомления) | `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` |
| Meilisearch | Поиск/фильтры | `MEILISEARCH_HOST`, `MEILISEARCH_KEY` |
| Reverb/Pusher | WebSocket для чата | `REVERB_APP_KEY`, `REVERB_APP_SECRET` |

Никаких ключей/URL в коде или конфигах по умолчанию — только `env()` внутри `config/*.php`,
использование `env()` напрямую вне `config/` запрещено (правило —
[`../rules/00-general.md`](../rules/00-general.md)).
