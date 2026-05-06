<?php

namespace Gigalog\Abstracts;

use Gigalog\Contracts\GigalogGroupEnum;
use Gigalog\Models\Gigalog;
use Gigalog\Support\GigalogAction;
use Gigalog\Support\GigalogEager;
use Gigalog\Support\GigalogEagerBag;
use Illuminate\Database\Eloquent\Model;

abstract class GigalogEvent
{
    /**
     * Версия класса события
     */
    public const string VERSION = '1.0.0';

    /**
     * Хранилище зависимостей
     */
    private ?GigalogEagerBag $_eagerBag = null;

    /**
     * Конструктор события
     */
    final public function __construct(
        protected Gigalog $gigalog
    ) {
        //
    }

    /**
     * Получить объект Gigalog
     */
    final public function getGigalog(): Gigalog
    {
        return $this->gigalog;
    }

    /**
     * Создать объект Gigalog
     */
    final public static function createGigalog(
        Model $subject,
        ?Model $causer = null,
        ?array $data = null
    ): self
    {
        $service = app(\Gigalog\Services\GigalogService::class);
        return new static($service->create(static::class, $subject, $causer, $data));
    }

    /**
     * Получить коллекцию зависимостей
     */
    public function getEagerBag(): GigalogEagerBag
    {
        return new GigalogEagerBag();
    }

    /**
     * Получить сообщение события
     */
    abstract public function getMessage(): string;

    /**
     * Получить заголовок события
     */
    public function getTitle(): ?string
    {
        return null;
    }

    /**
     * Получить действие события
     */
    public function getAction(): ?GigalogAction
    {
        return null;
    }

    /**
     * Получить группу события
     */
    public static function getGroup(): ?GigalogGroupEnum
    {
        return null;
    }
}
