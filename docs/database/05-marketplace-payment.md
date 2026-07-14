# Marketplace и Payment (эскроу-сделки)

[← к оглавлению БД](../DATABASE.md) · соглашения — [`00-conventions.md`](./00-conventions.md)

Модули: `Marketplace`, `Payment`. Бизнес-флоу и state machine —
[`../plan/08-flow-marketplace-escrow.md`](../plan/08-flow-marketplace-escrow.md).

## `listings`
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| seller_id | ULID FK → users | заводчик, должен быть верифицирован |
| pet_id | ULID FK → pets | |
| price_amount | bigint | копейки, см. соглашения |
| currency | char(3) default `RUB` | |
| status | enum(`draft`,`published`,`reserved`,`sold`,`archived`) default `draft` | |
| created_at / updated_at / deleted_at | timestamptz | |

## `orders` (сделка)
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| listing_id | FK → listings | |
| buyer_id | ULID FK → users | |
| seller_id | ULID FK → users | денормализовано из listing на момент покупки |
| amount | bigint | сумма сделки, копейки |
| commission_amount | bigint | 5% от `amount`, зафиксировано на момент оплаты (тариф мог измениться позже) |
| payout_amount | bigint | `amount - commission_amount` |
| status | enum(`pending_payment`,`paid_escrow`,`completed`,`disputed`,`refunded`,`cancelled`) | см. state machine в плане |
| buyer_confirmed_at | timestamptz nullable | |
| seller_confirmed_at | timestamptz nullable | |
| escrow_hold_until | timestamptz nullable | `paid_at + 7 days`, используется джобой авто-подтверждения |
| created_at / updated_at | timestamptz | |

## `order_status_history`
`id, order_id FK, from_status, to_status, actor_user_id FK nullable (null = система/крон),
reason text nullable, created_at` — обязателен по конвенции (state machine).

## `payments` (эскроу-платежи через ЮKassa)
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| payable_type / payable_id | polymorphic | `Order` или `Subscription` (см. `06-subscription.md`) |
| yookassa_payment_id | varchar unique | id платежа в ЮKassa |
| idempotency_key | varchar unique | обязателен на каждый вызов ЮKassa |
| amount | bigint | копейки |
| status | enum(`pending`,`waiting_for_capture`,`succeeded`,`canceled`,`refunded`) | зеркалит статусы ЮKassa |
| raw_payload | jsonb | сырой ответ/вебхук для аудита |
| created_at / updated_at | timestamptz | |

## `payouts` (выплаты продавцам)
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| order_id | FK → orders, unique | одна выплата на сделку |
| seller_id | ULID FK → users | |
| amount | bigint | = `orders.payout_amount` |
| status | enum(`pending`,`processing`,`paid`,`failed`) default `pending` | |
| yookassa_payout_id | varchar nullable | |
| processed_at | timestamptz nullable | |
| created_at | timestamptz | |

## `disputes`
`id, order_id FK unique, opened_by FK → users, reason text, resolution enum(`seller_wins`,
`buyer_wins`) nullable, resolved_by FK → users nullable, resolved_at nullable, created_at`.

Правила по деньгам (идемпотентность, вебхуки, авто-подтверждение через 7 дней) — строго в
[`../rules/04-payments-escrow.md`](../rules/04-payments-escrow.md).
