<?php

namespace Gigalog\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:gigalog')]
class MakeGigalogCommand extends GeneratorCommand
{
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/GigalogEvent.php.stub');
    }

    protected function resolveStubPath(string $stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../..'.$stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        $namespace = $rootNamespace.'\Gigalogs';

        if ($this->option('namespace')) {
            $namespace .= '\\'.$this->option('namespace');
        }

        return $namespace;
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        // Заменяем {{ name }} на имя класса в snake_case для использования в ключах локализации
        $name = str_replace('GigalogEvent', '', class_basename($name));
        $name = Str::snake($name);

        return str_replace('{{ name }}', $name, $stub);
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the notification already exists'],
            ['namespace', null, InputOption::VALUE_OPTIONAL, 'The namespace for the notification class (e.g., Front)'],
        ];
    }
}
