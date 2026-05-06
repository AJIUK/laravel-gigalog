<?php

namespace Gigalog\Support;

use Illuminate\Support\Collection;

class GigalogEagerBag
{
    private array $eagers = [];
    private array $classes = [];

    public function __construct() {}

    /**
     * Добавить зависимость
     */
    public function addEager(GigalogEager $eager): self
    {
        $this->eagers[] = $eager;
        return $this;
    }

    /**
     * Получить коллекцию зависимостей
     */
    public function getEagers(): array
    {
        return $this->eagers;
    }

    /**
     * Объединить другую коллекцию зависимостей
     */
    public function mergeBag(GigalogEagerBag $eagerBag): self
    {
        foreach ($eagerBag->getEagers() as $eager) {
            $this->addEager($eager);
        }
        return $this;
    }

    /**
     * Загрузить зависимости
     */
    public function load(): self
    {
        foreach ($this->eagers as &$eager) {
            $ownerClass = $eager->getOwnerClass();
            $ownerKey = $eager->getOwnerKey();

            if (!isset($this->classes[$ownerClass][$ownerKey])) {
                $this->classes[$ownerClass][$ownerKey] = [
                    'values' => [],
                    'eagers' => [],
                ];
            }

            $item = $this->classes[$ownerClass][$ownerKey];
            $item['values'] = array_merge($item['values'], $eager->getValues());
            $item['eagers'][] = $eager;
            $this->classes[$ownerClass][$ownerKey] = $item;
        }

        foreach ($this->classes as $ownerClass => $ownerKeys) {
            $query = $ownerClass::query();
            foreach ($ownerKeys as $ownerKey => $item) {
                $query->orWhereIn($ownerKey, $item['values']);
            }
            $items = $query->get();
            foreach ($ownerKeys as $ownerKey => $item) {
                foreach ($item['eagers'] as $eager) {
                    $eager->setItems($items->whereIn($ownerKey, $item['values']));
                }
            }
        }

        return $this;
    }
}
