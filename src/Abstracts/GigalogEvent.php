<?php

namespace Gigalog\Abstracts;

use Gigalog\Contracts\GigalogGroupEnum;
use Gigalog\Models\Gigalog;
use Gigalog\Services\GigalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class GigalogEvent
{
    public const string VERSION = '1.0.0';

    /**
     * Получить группу события
     */
    abstract public static function getGroup(): GigalogGroupEnum;

    /**
     * Получить сообщение события
     */
    abstract public static function getMessage(GigalogResGen $resGen): string;

    /**
     * Получить субъект события
     */
    abstract public function getSubject(): Model;

    /**
     * Получить causer события
     */
    abstract public function getCauser(): ?Model;

    /**
     * Создать объект Gigalog
     */
    final public function createGigalog(): Gigalog
    {
        $service = app(GigalogService::class);
        $gigalog = $service->create($this);
        $this->gigalogCreated($gigalog);
        return $gigalog;
    }

    /**
     * Получить код события
     */
    final public static function getCode(): string
    {
        return collect(explode('\\', static::class))
            ->map(fn (string $part) => Str::snake($part))
            ->implode('.');
    }

    /**
     * Получить подготовленные данные для сохранения
     */
    final public function getPreparedData(): ?array
    {
        $resGen = static::prepareResGen(new Gigalog());
        return $resGen::prepareSaveData($this);
    }

    /**
     * Подготовить объект GigalogResGen для загрузки данных и использовании в ресурсе
     */
    abstract public static function prepareResGen(Gigalog $gigalog): GigalogResGen;

    /**
     * Действие после создания Gigalog
     * Например, можно создать связи между созданным логом и прочими моделями
     */
    public function gigalogCreated(Gigalog $gigalog): void
    {
        //
    }
}
