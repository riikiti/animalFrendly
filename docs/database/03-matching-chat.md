# Matching и Chat

[← к оглавлению БД](../DATABASE.md) · соглашения — [`00-conventions.md`](./00-conventions.md)

Модули: `Matching`, `Chat`. Бизнес-флоу — [`../plan/07-flow-matching-shelter.md`](../plan/07-flow-matching-shelter.md).

## `swipes`
| Поле | Тип | Примечание |
|---|---|---|
| id | bigint PK | высокообъёмная таблица, партиционирование по месяцу в проде |
| swiper_pet_id | ULID FK → pets | чья анкета свайпает |
| target_pet_id | ULID FK → pets | |
| action | enum(`like`,`dislike`,`super_like`) | |
| created_at | timestamptz | |

Индекс `unique(swiper_pet_id, target_pet_id)` — повторный свайп невозможен (сброс — через
удаление записи по крон-джобе free-лимита).

## `matches`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| pet_a_id / pet_b_id | ULID FK → pets | `pet_a_id < pet_b_id` по конвенции, чтобы избежать дублей |
| matched_at | timestamptz | |
| unmatched_at | timestamptz nullable | одна из сторон разорвала мэтч |

## `conversations`
`id (ULID PK), match_id FK → matches nullable, adoption_request_id FK nullable, created_at`
— чат может быть привязан либо к мэтчу, либо к заявке на усыновление (см.
[`04-shelter.md`](./04-shelter.md)); ровно один из двух FK заполнен (CHECK constraint).

## `messages`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| conversation_id | FK → conversations | |
| sender_id | FK → users | |
| body | text | |
| attachment_media_id | ULID FK → media, nullable | |
| read_at | timestamptz nullable | |
| created_at | timestamptz | |

Доставка в реальном времени — через Reverb/Echo (см.
[`../plan/02-tech-stack.md`](../plan/02-tech-stack.md)), таблица — источник истины и офлайн-синк.
