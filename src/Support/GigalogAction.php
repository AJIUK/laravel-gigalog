<?php

namespace Gigalog\Support;

class GigalogAction
{
    public function __construct(
        private string $label,
        private string $url,
    ) {}

    /**
     * Получить label действия
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Получить url действия
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
