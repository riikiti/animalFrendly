# Notification, Media, Moderation

[← к оглавлению БД](../DATABASE.md) · соглашения — [`00-conventions.md`](./00-conventions.md)

Модули: `Notification`, `Media`, `Moderation`.

## `media`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| owner_user_id | ULID FK → users nullable | кто загрузил |
| disk | varchar | ключ диска из `config/filesystems.php` (S3-совместимое хранилище) |
| path | varchar | |
| mime_type | varchar | |
| size_bytes | bigint | |
| created_at | timestamptz | |

Используется через `media_id`-ссылки из других таблиц (`pet_photos`, `user_profiles.avatar_media_id`,
`shelter_verification_documents`, `messages.attachment_media_id`) — без polymorphic-связи,
явные FK читабельнее и безопаснее для миграций.

## `device_tokens` (push)
`id, user_id FK, platform enum(`android`,`ios`), fcm_token varchar, created_at, last_used_at`.

## `notifications`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| user_id | ULID FK → users | |
| type | varchar | `new_match`, `new_message`, `adoption_approved`, `deal_completed`, ... |
| payload | jsonb | данные для рендера уведомления |
| channel | enum(`push`,`email`,`in_app`) | |
| read_at | timestamptz nullable | |
| sent_at | timestamptz nullable | |
| created_at | timestamptz | |

## `reports` (жалобы)
`id, reporter_id FK → users, target_type varchar, target_id varchar, reason varchar,
comment text nullable, status enum(`pending`,`reviewed`,`dismissed`), reviewed_by FK nullable,
created_at`.

## `reviews` (отзывы после сделки/усыновления)
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| order_id | FK → orders nullable | см. `05-marketplace-payment.md` |
| adoption_request_id | FK → adoption_requests nullable | см. `04-shelter.md` |
| author_id | ULID FK → users | |
| target_user_id | ULID FK → users | о ком отзыв (продавец/приют) |
| rating | smallint | 1..5 |
| comment | text nullable | |
| created_at | timestamptz | |

Ровно один из `order_id`/`adoption_request_id` заполнен (CHECK constraint).

## `audit_logs`
`id, actor_id FK → users nullable, action varchar, entity_type varchar, entity_id varchar,
payload jsonb, created_at` — общий журнал критичных действий (верификации, решения по
спорам, ручные выплаты, изменение тарифов).
