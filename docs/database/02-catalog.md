# Catalog (виды и породы)

[← к оглавлению БД](../DATABASE.md) · соглашения — [`00-conventions.md`](./00-conventions.md)

Модуль: `Catalog`. Справочник — не удаляется физически, только `is_active`.

## `species` (виды животных)
| Поле | Тип | Примечание |
|---|---|---|
| id | smallint PK | |
| slug | varchar unique | `dog`, `cat`, `bird`, ... |
| name_ru | varchar | |
| icon_media_id | ULID FK → media, nullable | |
| is_active | boolean default true | |

## `breeds` (породы)
| Поле | Тип | Примечание |
|---|---|---|
| id | int PK | |
| species_id | FK → species | |
| slug | varchar | уникален в рамках вида |
| name_ru | varchar | |
| attributes | jsonb nullable | доп. атрибуты породы (размер, тип шерсти и т.п.) — гибкая схема |
| is_active | boolean default true | |

Индекс `unique(species_id, slug)`. Используется для фильтров в `Matching` и `Marketplace`
(поиск через Meilisearch синхронизируется по `species_id`/`breed_id`, см.
[`../plan/02-tech-stack.md`](../plan/02-tech-stack.md)).
