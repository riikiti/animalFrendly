# Shelter (приюты и усыновление)

[← к оглавлению БД](../DATABASE.md) · соглашения — [`00-conventions.md`](./00-conventions.md)

Модуль: `Shelter`. Бизнес-флоу — [`../plan/07-flow-matching-shelter.md`](../plan/07-flow-matching-shelter.md).

## `shelters`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| owner_user_id | ULID FK → users | администратор аккаунта приюта |
| legal_name | varchar | название НКО/юрлица |
| inn | varchar nullable | |
| description | text nullable | |
| verification_status | enum(`pending`,`verified`,`rejected`) default `pending` | |
| verified_at | timestamptz nullable | |
| verified_by | ULID FK → users nullable | модератор |
| created_at / updated_at / deleted_at | timestamptz | |

## `shelter_verification_documents`
`id, shelter_id FK, media_id FK → media, doc_type varchar, uploaded_at` — пакет документов
на верификацию, привязан к очереди модерации (`Moderation`, см.
[`07-notification-media-moderation.md`](./07-notification-media-moderation.md)).

## `shelter_animals`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| shelter_id | FK → shelters | |
| pet_id | ULID FK → pets | сама анкета животного (фото/вид/порода — в `pets`) |
| status | enum(`available`,`reserved`,`adopted`,`removed`) default `available` | |
| created_at / updated_at | timestamptz | |

## `adoption_requests`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| shelter_animal_id | FK → shelter_animals | |
| requester_user_id | ULID FK → users | |
| status | enum(`pending`,`approved`,`rejected`,`cancelled`) default `pending` | |
| message | text nullable | сопроводительное сообщение |
| decided_at | timestamptz nullable | |
| decided_by | ULID FK → users nullable | сотрудник приюта |
| created_at / updated_at | timestamptz | |

## `adoption_request_status_history`
`id, adoption_request_id FK, from_status, to_status, changed_by FK → users, created_at` —
аудит-журнал по конвенции [`00-conventions.md`](./00-conventions.md).

Переписка по заявке — через `conversations.adoption_request_id`, см.
[`03-matching-chat.md`](./03-matching-chat.md).
