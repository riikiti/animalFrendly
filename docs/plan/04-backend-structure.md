# Backend: структура папок (Laravel, DDD, модули)

[← к оглавлению плана](../PLAN.md)

Модульный DDD: каждый bounded context из [`01-modules.md`](./01-modules.md) — самостоятельный
модуль с 4 слоями (Domain / Application / Infrastructure / Presentation) и собственными
тестами рядом.

```
backend/
├── app/
│   ├── Modules/
│   │   ├── Identity/
│   │   │   ├── Domain/
│   │   │   │   ├── Entities/
│   │   │   │   ├── ValueObjects/
│   │   │   │   ├── Repositories/         # интерфейсы
│   │   │   │   ├── Events/
│   │   │   │   ├── Exceptions/
│   │   │   │   └── Services/             # доменные сервисы (без фреймворка)
│   │   │   ├── Application/
│   │   │   │   ├── Commands/             # UseCase-команды (1 класс = 1 действие)
│   │   │   │   ├── Queries/
│   │   │   │   └── DTO/
│   │   │   ├── Infrastructure/
│   │   │   │   ├── Persistence/Eloquent/Models/
│   │   │   │   ├── Persistence/Eloquent/Repositories/
│   │   │   │   ├── External/             # адаптеры (напр. соц-логин)
│   │   │   │   └── Providers/IdentityServiceProvider.php
│   │   │   └── Presentation/
│   │   │       ├── Http/Controllers/
│   │   │       ├── Http/Requests/
│   │   │       ├── Http/Resources/
│   │   │       └── routes.php
│   │   ├── Profile/          {Domain,Application,Infrastructure,Presentation}
│   │   ├── Catalog/          {...}       # виды, породы
│   │   ├── Matching/         {...}       # свайпы, мэтчи
│   │   ├── Chat/             {...}
│   │   ├── Shelter/          {...}       # приюты, усыновление
│   │   ├── Marketplace/      {...}       # листинги, сделки
│   │   ├── Payment/          {...}       # ЮKassa, эскроу, выплаты
│   │   ├── Subscription/     {...}       # тарифы, биллинг
│   │   ├── Notification/     {...}
│   │   ├── Moderation/       {...}       # жалобы, отзывы, баны
│   │   ├── Media/            {...}
│   │   └── Admin/            {...}       # тонкий слой поверх других модулей
│   └── Shared/                            # kernel, общий для всех модулей
│       ├── Domain/
│       │   ├── AggregateRoot.php
│       │   ├── DomainEvent.php
│       │   ├── ValueObjects/Money.php    # деньги — целые копейки, никогда float
│       │   └── ValueObjects/Id.php       # UUID/ULID
│       ├── Application/EventBus/
│       └── Infrastructure/               # общие адаптеры (S3, логирование)
├── config/                                # весь конфиг читает значения из env()
│   ├── yookassa.php
│   ├── fcm.php
│   └── filesystems.php
├── database/
│   ├── migrations/                        # префикс по модулю: 2024_..._identity_create_users
│   ├── factories/                         # сгруппированы по модулям в подпапках
│   └── seeders/
├── routes/
│   └── api.php                            # require каждого Modules/*/Presentation/routes.php
├── tests/
│   ├── Unit/Modules/{Module}/Domain/      # чистые тесты домена, без БД
│   ├── Unit/Modules/{Module}/Application/ # UseCase с замоканными репозиториями
│   ├── Feature/Modules/{Module}/Http/     # HTTP тесты с реальной БД (sqlite/pgsql testing)
│   └── Integration/Modules/{Module}/      # интеграции с внешними адаптерами (sandbox/фейки)
└── .env.example                           # все ключи внешних сервисов, без значений
```

Domain-слой не имеет зависимостей от Illuminate/Eloquent — это чистый PHP, что и
позволяет тестировать бизнес-правила (например, state machine сделки) без БД и HTTP.

Правила кода для этого слоя — [`../rules/01-backend.md`](../rules/01-backend.md).
Структура таблиц БД по модулям — [`../DATABASE.md`](../DATABASE.md).
