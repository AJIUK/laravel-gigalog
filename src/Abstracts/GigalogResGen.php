<?php

namespace Gigalog\Abstracts;

use Gigalog\Models\Gigalog;
use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Contracts\GigalogGroupEnum;
use Gigalog\Support\GigalogAction;
use Gigalog\Support\GigalogEagerBag;
use Illuminate\Support\Str;

abstract class GigalogResGen
{
    /**
     * @param Gigalog $gigalog
     */
    final public function __construct(
        public readonly Gigalog $gigalog,
    ) {
        //
    }

    /**
     * Хранилище объекта GigalogEagerBag
     */
    private ?GigalogEagerBag $_eagerBag = null;

    /**
     * Установить объект GigalogEagerBag
     */
    abstract public function setEagerBag(Gigalog $gigalog): GigalogEagerBag;

    /**
     * Получить объект GigalogEagerBag
     */
    final public function getEagerBag(): GigalogEagerBag
    {
        if (!isset($this->_eagerBag)) {
            $this->_eagerBag = $this->setEagerBag($this->gigalog);
        }
        return $this->_eagerBag;
    }

    /**
     * Хранилище сообщения генератора
     */
    private ?string $_message = null;

    /**
     * Получить сообщение события
     */
    final public function getMessage(): string
    {
        if (!isset($this->_message)) {
            $this->_message = $this->gigalog->getEventClass()::getMessage($this);
        }
        return $this->_message;
    }

    /**
     * Хранилище объекта GigalogAction
     */
    private ?GigalogAction $_action = null;

    /**
     * Установить объект GigalogAction
     */
    public function setAction(Gigalog $gigalog): ?GigalogAction
    {
        return null;
    }

    /**
     * Получить объект GigalogAction
     */
    final public function getAction(): GigalogAction
    {
        if (!isset($this->_action)) {
            $this->_action = $this->setAction($this->gigalog);
        }
        return $this->_action;
    }

    /**
     * Подготовить данные для сохранения
     */
    abstract public static function prepareSaveData(GigalogEvent $event): ?array;

    // final public function getCode(): string
    // {
    //     return $this->gigalog->getEventClass()::getCode();
    // }
    final public static function getCode(): string
    {
        return collect(explode('\\', static::class))
            ->map(fn (string $part) => Str::snake($part))
            ->implode('.');
    }

    final public function getGroup(): ?GigalogGroupEnum
    {
        return $this->gigalog->getEventClass()::getGroup();
    }

    private ?array $_meta = null;

    abstract public function setMeta(Gigalog $gigalog): ?array;

    final public function getMeta(): ?array
    {
        if (!isset($this->_meta)) {
            $this->_meta = $this->setMeta($this->gigalog);
        }
        return $this->_meta;
    }
}
