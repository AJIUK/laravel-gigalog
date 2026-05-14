# Laravel Gigalog

`laravel-gigalog` — библиотека для событийных логов с morph-связями на `subject` и `causer`.

## Установка

```bash
composer require ajiuk/laravel-gigalog
```

Если auto-discovery отключен, добавьте провайдер вручную:

```php
Gigalog\Providers\GigalogServiceProvider::class,
```

Для Laravel 11/12 регистрируйте в `bootstrap/providers.php`.

## Публикация

Опубликовать все ресурсы:

```bash
php artisan vendor:publish --tag=gigalog
```

Только конфиг:

```bash
php artisan vendor:publish --tag=gigalog-config
```

Только миграции:

```bash
php artisan vendor:publish --tag=gigalog-migrations
```

Только ресурс:

```bash
php artisan vendor:publish --tag=gigalog-resource
```

После публикации миграций:

```bash
php artisan migrate
```

## Конфигурация

Файл `config/gigalog.php`:

- `gigalog_model` — класс модели логов (по умолчанию `Gigalog\Models\Gigalog`)
- `gigalog_table` — имя таблицы (по умолчанию `gigalogs`)

Поддерживаемые переменные окружения:

- `GIGALOG_MODEL`
- `GIGALOG_TABLE`

## Генерация событий

Пакет добавляет команду:

```bash
php artisan make:gigalog-event OrderCreatedEvent
```

Команда генерирует **два класса**:

- событие в `App\Gigalog\Events\...`
- res-generator в `App\Gigalog\ResGen\...ResGen`

Опции:

- `--namespace=Front` — добавляет под-namespace (например, `App\Gigalog\Events\Front`)
- `--force` (`-f`) — перезаписывает существующие файлы

## Архитектура: `GigalogEvent` + `GigalogResGen`

### `GigalogEvent`

`Gigalog\Abstracts\GigalogEvent` описывает данные для сохранения и связь с доменной моделью:

- `getSubject(): Model` — обязателен
- `getCauser(): ?Model` — обязателен
- `prepareResGen(Gigalog $gigalog): GigalogResGen` — обязателен
- `setGroup(): ?GigalogGroupEnum` — опционально
- `gigalogCreated(Gigalog $gigalog): void` — опциональный lifecycle-хук

Также:

- `VERSION` сохраняется в поле `version`
- `getCode()` строит код события из FQCN (snake_case с разделителем `.`)
- `createGigalog()` создает запись через `GigalogService`

### `GigalogResGen`

`Gigalog\Abstracts\GigalogResGen` отвечает за отображение лога и lazy-кеширование:

- `setEagerBag(Gigalog $gigalog): GigalogEagerBag` — объявление зависимостей для батч-загрузки
- `setMessage(Gigalog $gigalog): string` — текст события
- `setMeta(Gigalog $gigalog): ?array` — meta-данные для API
- `prepareSaveData(GigalogEvent $event): ?array` (static) — данные для поля `data`
- `setAction(Gigalog $gigalog): ?GigalogAction` — опциональное действие (по умолчанию `null`)

## Пример группы (`GigalogGroupEnum`)

```php
<?php

namespace App\Enums;

use Gigalog\Contracts\GigalogGroupEnum;

enum GigalogGroup: string implements GigalogGroupEnum
{
    case ORDERS = 'orders';

    public function getName(): string
    {
        return 'Orders';
    }

    public function getCode(): string
    {
        return $this->value;
    }
}
```

## Пример сгенерированного события

```php
<?php

namespace App\Gigalog\Events;

use App\Enums\GigalogGroup;
use App\Gigalog\ResGen\OrderCreatedEventResGen;
use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Abstracts\GigalogResGen;
use Gigalog\Contracts\GigalogGroupEnum;
use Gigalog\Models\Gigalog;
use Illuminate\Database\Eloquent\Model;

class OrderCreatedEvent extends GigalogEvent
{
    public const string VERSION = '1.0.0';

    public function __construct(
        public readonly Model $subject,
        public readonly ?Model $causer = null,
    ) {
        //
    }

    public static function setGroup(): ?GigalogGroupEnum
    {
        return GigalogGroup::ORDERS;
    }

    public function getSubject(): Model
    {
        return $this->subject;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }

    public static function prepareResGen(Gigalog $gigalog): GigalogResGen
    {
        return new OrderCreatedEventResGen($gigalog);
    }
}
```

## Пример `ResGen`

```php
<?php

namespace App\Gigalog\ResGen;

use App\Gigalog\Events\OrderCreatedEvent;
use App\Models\User;
use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Abstracts\GigalogResGen;
use Gigalog\Models\Gigalog;
use Gigalog\Support\GigalogEager;
use Gigalog\Support\GigalogEagerBag;

class OrderCreatedEventResGen extends GigalogResGen
{
    public ?GigalogEager $userEager = null;

    public function setEagerBag(Gigalog $gigalog): GigalogEagerBag
    {
        $bag = new GigalogEagerBag();

        $bag->addEager(new GigalogEager(
            User::class,
            'id',
            [$gigalog->data['user_id'] ?? null],
            $this->userEager
        ));

        return $bag;
    }

    public function setMessage(Gigalog $gigalog): string
    {
        $userName = $this->userEager?->getItems()?->first()?->name ?? 'Unknown';

        return "Order created by {$userName}";
    }

    public static function prepareSaveData(GigalogEvent $event): ?array
    {
        if (!$event instanceof OrderCreatedEvent) {
            return null;
        }

        return [
            'user_id' => $event->causer?->getKey(),
        ];
    }

    public function setMeta(Gigalog $gigalog): ?array
    {
        return [
            'subject_id' => $gigalog->subject_id,
            'causer_id' => $gigalog->causer_id,
        ];
    }
}
```

## Создание лога

```php
use App\Gigalog\Events\OrderCreatedEvent;

$gigalog = (new OrderCreatedEvent(
    subject: $order,
    causer: auth()->user(),
))->createGigalog();
```

## Получение списка логов

`GigalogService::list()`:

- сортирует по `created_at desc`
- фильтрует по `subject`, `causer`, `group`
- подгружает `subject` и `causer`
- автоматически вызывает `loadEagers()` для текущей страницы

Пример:

```php
$items = $gigalogService->list(
    subject: $request->user(),
    causer: null,
    group: $request->string('group')->toString() ?: null,
    perPage: (int) $request->integer('per_page', 20),
);
```

## `GigalogResource` (stub)

Публикуемый ресурс `GigalogResource` использует `getResGen()` и возвращает:

- `id`
- `code`
- `group` (`name`, `code`)
- `meta`
- `message`
- `created_at`
- `updated_at`

## Модель `Gigalog`

`Gigalog\Models\Gigalog`:

- хранит `class_name`, `version`, `group`, `data`
- имеет morph-связи `subject()` и `causer()`
- восстанавливает событие через `getEventClass()`
- строит res-generator через `getResGen()`

Если `class_name` не существует или не наследуется от `GigalogEvent`, `getEventClass()` вернет `null`.
