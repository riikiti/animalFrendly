# Subscription

[← к оглавлению БД](../DATABASE.md) · соглашения — [`00-conventions.md`](./00-conventions.md)

Модуль: `Subscription`. Бизнес-флоу — [`../plan/09-flow-subscriptions.md`](../plan/09-flow-subscriptions.md).

## `subscription_plans`
| Поле | Тип | Примечание |
|---|---|---|
| id | smallint PK | |
| slug | varchar unique | `free`, `plus`, `premium` |
| name_ru | varchar | |
| price_amount | bigint | копейки, за период |
| period | enum(`month`,`year`) | |
| features | jsonb | ключи фич и лимиты, напр. `{"daily_likes": null, "super_likes_per_week": 5, "marketplace_commission_bps": 500}` |
| is_active | boolean default true | |

## `subscriptions` (подписка пользователя)
| Поле | Тип | Примечание |
|---|---|---|
| id | ULID PK | |
| user_id | ULID FK → users | |
| plan_id | FK → subscription_plans | |
| status | enum(`active`,`canceled`,`expired`,`past_due`) | |
| started_at | timestamptz | |
| current_period_ends_at | timestamptz | |
| auto_renew | boolean default true | |
| canceled_at | timestamptz nullable | |
| created_at / updated_at | timestamptz | |

Оплата/автопродление — записи в `payments` (см.
[`05-marketplace-payment.md`](./05-marketplace-payment.md), `payable_type = Subscription`).

## `feature_usage` (расход лимитированных ресурсов)
| Поле | Тип | Примечание |
|---|---|---|
| id | bigint PK | |
| user_id | ULID FK → users | |
| feature_key | varchar | `super_like`, `boost`, `daily_like` |
| period_start | date | окно лимита (день/неделя/месяц — зависит от фичи) |
| used_count | int default 0 | |

Индекс `unique(user_id, feature_key, period_start)`. Проверка и списание лимита —
атомарная операция в Application-слое `Subscription` (`SubscriptionFeatureGate`, см. план),
не в контроллерах других модулей.
