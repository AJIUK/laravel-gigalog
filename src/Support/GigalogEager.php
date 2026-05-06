<?php

namespace Gigalog\Support;

use Illuminate\Support\Collection;

class GigalogEager
{
    private ?Collection $items = null;

    public function __construct(
        private string $ownerClass,
        private string $ownerKey,
        private array $values,
        ?GigalogEager &$eager = null
    ) {
        $eager = $this;
    }

    /**
     * Получить класс владельца
     */
    public function getOwnerClass(): string
    {
        return $this->ownerClass;
    }

    /**
     * Получить ключ владельца
     */
    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }

    /**
     * Получить значения
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Добавить значения к текущим значениям
     */
    public function addValues(array $values): self
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    /**
     * Получить коллекцию загруженных моделей
     */
    public function getItems(): ?Collection
    {
        return $this->items;
    }

    /**
     * Установить коллекцию загруженных моделей
     */
    public function setItems(Collection $items): self
    {
        $this->items = $items;
        return $this;
    }
}
