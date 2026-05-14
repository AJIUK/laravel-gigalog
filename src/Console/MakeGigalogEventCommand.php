<?php

namespace Gigalog\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:gigalog-event')]
class MakeGigalogEventCommand extends GeneratorCommand
{
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

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
        $namespace = $rootNamespace.'\Gigalog\Events';

        if ($this->option('namespace')) {
            $namespace .= '\\'.$this->option('namespace');
        }

        return $namespace;
    }

    protected function buildClass($name)
    {
        return $this->replaceGigalogPlaceholders(parent::buildClass($name));
    }

    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the notification already exists'],
            ['namespace', null, InputOption::VALUE_OPTIONAL, 'The namespace for the notification class (e.g., Front)'],
        ];
    }

    public function handle()
    {
        $eventResult = parent::handle();

        if ($eventResult === false) {
            return false;
        }

        $resGenQualifiedName = $this->getQualifiedResGenClassName();
        $resGenPath = $this->getPath($resGenQualifiedName);

        if ((!$this->hasOption('force') || !$this->option('force')) && $this->alreadyExists($resGenQualifiedName)) {
            $this->components->error($this->type.' already exists.');

            return false;
        }

        $this->makeDirectory($resGenPath);
        $this->files->put($resGenPath, $this->buildResGenClass($resGenQualifiedName));
        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $resGenPath));

        return $eventResult;
    }

    protected function getQualifiedEventClassName(): string
    {
        return $this->qualifyClass($this->argument('name'));
    }

    protected function getQualifiedResGenClassName(): string
    {
        $eventQualifiedName = $this->getQualifiedEventClassName();
        $eventNamespace = Str::beforeLast($eventQualifiedName, '\\');
        $resGenNamespace = str_replace('\\Events', '\\ResGen', $eventNamespace);

        return $resGenNamespace.'\\'.class_basename($eventQualifiedName).'ResGen';
    }

    protected function buildResGenClass(string $name): string
    {
        $stub = $this->files->get($this->resolveStubPath('/stubs/GigalogResGen.php.stub'));
        $this->replaceNamespace($stub, $name);
        $stub = $this->replaceClass($stub, $name);

        return $this->replaceGigalogPlaceholders($stub);
    }

    protected function replaceGigalogPlaceholders(string $stub): string
    {
        $eventQualifiedName = $this->getQualifiedEventClassName();
        $resGenQualifiedName = $this->getQualifiedResGenClassName();
        $eventClass = class_basename($eventQualifiedName);
        $resGenClass = class_basename($resGenQualifiedName);
        $eventNamespace = trim(str_replace('\\'.$eventClass, '', $eventQualifiedName), '\\');
        $resGenNamespace = trim(str_replace('\\'.$resGenClass, '', $resGenQualifiedName), '\\');

        return str_replace(
            ['{{ event_namespace }}', '{{ res_gen_namespace }}', '{{ res_gen_class }}', '{{ event_class}}', '{{ event_class }}'],
            [$eventNamespace, $resGenNamespace, $resGenClass, $eventClass, $eventClass],
            $stub
        );
    }
}
