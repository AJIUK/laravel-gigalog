# Laravel Gigalog

`laravel-gigalog` — библиотека для хранения событийных логов с привязкой к `subject` и `causer` через morph-отношения.

## Установка

```bash
composer require ajiuk/laravel-gigalog
```

### Подключение Service Provider

Если в проекте не используется package auto-discovery, зарегистрируйте провайдер вручную:

```php
Gigalog\Providers\GigalogServiceProvider::class,
```

Для Laravel 11/12 добавьте его в `bootstrap/providers.php`.

## Публикация файлов пакета

Опубликовать все ресурсы пакета:

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

Только API-ресурс `GigalogResource` (из stubs):

```bash
php artisan vendor:publish --tag=gigalog-resource
```

После публикации миграций выполните:

```bash
php artisan migrate
```

## Конфигурация

Файл: `config/gigalog.php`

- `gigalog_model` — класс модели логов (должен наследоваться от `Gigalog\Models\Gigalog`)
- `gigalog_table` — имя таблицы для хранения логов

Можно переопределять через переменные окружения:

- `GIGALOG_MODEL`
- `GIGALOG_TABLE`

## Команда `make:gigalog`

Пакет добавляет artisan-команду:

```bash
php artisan make:gigalog OrderCreatedGigalogEvent
```

По умолчанию класс создается в namespace `App\Gigalogs`.

Опции:

- `--namespace=Front` — добавляет подпапку/namespace, итог: `App\Gigalogs\Front`
- `--force` — перезаписывает существующий файл

## Базовый класс `GigalogEvent`

Все события должны наследоваться от `Gigalog\Abstracts\GigalogEvent`.

Обязательное:

- `getMessage(): string` — текст события (обязательно к реализации)

Опционально можно переопределять:

- `getTitle(): ?string` — короткий заголовок события (по умолчанию `null`)
- `getAction(): ?Gigalog\Support\GigalogAction` — действие (кнопка/ссылка) для UI (по умолчанию `null`)
- `getEagerBag(): Gigalog\Support\GigalogEagerBag` — объявление зависимостей для батч-загрузки связанных данных (по умолчанию пустой `GigalogEagerBag`)
- `getGroup(): ?Gigalog\Contracts\GigalogGroupEnum` — группа события (по умолчанию `null`)

Доступные методы базового класса:

- `createGigalog(Model $subject, ?Model $causer = null, ?array $data = null): static` — создает запись в таблице логов и возвращает инстанс события
- `getGigalog(): Gigalog\Models\Gigalog` — возвращает модель лога, связанную с событием

Также в событии можно определить константу версии:

```php
public const string VERSION = '1.0.0';
```

Значение сохраняется в поле `version` при создании записи.

### Зачем нужна `version`

`version` помогает безопасно развивать событие во времени без поломки старых логов.

Типовые сценарии:

- изменить шаблон/формат `getMessage()` для новых записей, сохранив корректный рендер старых;
- поменять структуру `data` и обрабатывать старый/новый формат по `version`;
- использовать разные стратегии загрузки зависимостей в `getEagerBag()` для разных версий события.

Идея простая: при чтении лога вы смотрите `\$gigalog->version` и выбираете нужную ветку логики для отображения.

## Пример события

```php
<?php

namespace App\Gigalogs;

use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Support\GigalogAction;

class OrderCreatedGigalogEvent extends GigalogEvent
{
    public const string VERSION = '1.0.1';

    public function getMessage(): string
    {
        return 'Заказ создан';
    }

    public function getTitle(): ?string
    {
        return 'Новый заказ';
    }

    public function getAction(): ?GigalogAction
    {
        return new GigalogAction('Открыть заказ', '/orders/'.$this->getGigalog()->subject_id);
    }
}
```

## Создание лога из события

```php
use App\Gigalogs\OrderCreatedGigalogEvent;

OrderCreatedGigalogEvent::createGigalog(
    subject: $order,
    causer: auth()->user(),
    data: ['source' => 'admin-panel']
);
```

## Группы через `GigalogGroupEnum`

Группа задается в событии через `getGroup()`. Код группы будет автоматически сохранен в поле `group`.

Пример `GigalogGroup.php` как реализации `GigalogGroupEnum`:

```php
<?php

namespace App\Http\Enums;

use Gigalog\Contracts\GigalogGroupEnum;

enum GigalogGroup: string implements GigalogGroupEnum
{
    case DEFAULT = 'default';
    case ORDERS = 'orders';
    case USERS = 'users';

    public function getName(): string
    {
        return match ($this) {
            self::DEFAULT => 'Default',
            self::ORDERS => 'Orders',
            self::USERS => 'Users',
        };
    }

    public function getCode(): string
    {
        return $this->value;
    }
}
```

Использование enum в событии:

```php
<?php

namespace App\Gigalogs;

use App\Http\Enums\GigalogGroup;
use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Contracts\GigalogGroupEnum;

class OrderCreatedGigalogEvent extends GigalogEvent
{
    public static function getGroup(): ?GigalogGroupEnum
    {
        return GigalogGroup::ORDERS;
    }

    public function getMessage(): string
    {
        return 'Заказ создан';
    }
}
```

## Пример `GigalogService::list()` в контроллере

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GigalogResource;
use Gigalog\Services\GigalogService;
use Illuminate\Http\Request;

class GigalogController extends Controller
{
    public function index(Request $request, GigalogService $gigalogService)
    {
        $items = $gigalogService->list(
            subject: $request->user(),
            causer: null,
            group: $request->string('group')->toString() ?: null,
            perPage: (int) $request->integer('per_page', 20),
        );

        return GigalogResource::collection($items);
    }
}
```

Важно: `GigalogService::list()` внутри себя уже вызывает `loadEagers(...)`, поэтому зависимости из `getEagerBag()` будут загружены автоматически для всех элементов страницы.

## Полный пример `getEagerBag()` в событии

Ниже пример, близкий к реальному использованию:

```php
<?php

namespace App\Gigalogs;

use App\Models\RealtyObject;
use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Support\GigalogEager;
use Gigalog\Support\GigalogEagerBag;

class TestLog extends GigalogEvent
{
    public ?GigalogEager $objectEager = null;
    public ?GigalogEager $objectsEager = null;

    public function getEagerBag(): GigalogEagerBag
    {
        $bag = new GigalogEagerBag();

        $bag->addEager(new GigalogEager(
            RealtyObject::class,
            'id',
            [$this->gigalog->data['object_id']],
            $this->objectEager
        ));

        $bag->addEager(new GigalogEager(
            RealtyObject::class,
            'project_id',
            [$this->gigalog->data['project_id']],
            $this->objectsEager
        ));

        return $bag;
    }

    public function getMessage(): string
    {
        $count = $this->objectsEager?->getItems()?->count() ?? 0;

        return "{$count} objects found";
    }
}
```

### Для чего нужен параметр `eager` в конструкторе `GigalogEager`

Сигнатура:

```php
new GigalogEager(
    string $ownerClass,
    string $ownerKey,
    array $values,
    ?GigalogEager &$eager = null
)
```

Последний параметр (`&$eager`) передается **по ссылке** и нужен, чтобы сохранить созданный объект `GigalogEager` в свойство события (например, `$this->objectsEager`), а затем после `load()` получить доступ к загруженным `items`.

Именно поэтому в примере выше можно использовать:

- `$this->objectEager?->getItems()`
- `$this->objectsEager?->getItems()`

## Ручная загрузка зависимостей через `GigalogEagerBag::load()`

Если вы не используете `GigalogService::list()` (или хотите управлять процессом вручную), можно загрузить зависимости так:

```php
use Gigalog\Models\Gigalog;
use Gigalog\Support\GigalogEagerBag;

$logs = Gigalog::query()
    ->latest()
    ->with(['subject', 'causer'])
    ->limit(20)
    ->get();

$bag = new GigalogEagerBag();

foreach ($logs as $log) {
    $event = $log->getEvent();
    if (!$event) {
        continue;
    }

    $bag->mergeBag($event->getEagerBag());
}

$bag->load();
```

После `load()` значения можно доставать из сохраненных eager-объектов внутри события:

```php
$event = $log->getEvent();

$object = $event?->objectEager?->getItems()?->first();     // один объект по id
$objects = $event?->objectsEager?->getItems() ?? collect(); // коллекция по project_id
```

## Модель `Gigalog`

Модель `Gigalog\Models\Gigalog`:

- хранит имя класса события в `class_name`
- умеет восстанавливать событие через `getEvent()`
- содержит morph-связи `subject()` и `causer()`

Если класс в `class_name` не найден или не наследуется от `GigalogEvent`, `getEvent()` вернет `null`.
