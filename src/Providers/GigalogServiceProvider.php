<?php

namespace Gigalog\Providers;

use Gigalog\Models\Gigalog;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class GigalogServiceProvider extends ServiceProvider
{
    public function getPath(string $path): string
    {
        return __DIR__ . '/../../' . $path;
    }

    public function register()
    {
        $this->mergeConfigFrom($this->getPath('config/gigalog.php'), 'gigalog');
        $this->app->singleton(Gigalog::class);
    }

    public function boot()
    {
        $this->loadTranslationsFrom($this->getPath('lang'), 'gigalog');
        $this->offerPublishing();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Gigalog\Console\MakeGigalogEventCommand::class,
            ]);
        }
    }

    protected function offerPublishing(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            $this->getPath('config/gigalog.php') => config_path('gigalog.php'),
        ], ['gigalog', 'gigalog-config']);

        $this->publishes([
            $this->getPath('database/migrations/create_gigalogs_table.php') => $this->getMigrationFileName('create_gigalogs_table.php'),
        ], ['gigalog', 'gigalog-migrations']);

        $this->publishes([
            $this->getPath('stubs/GigalogResource.php.stub') => app_path('Http/Resources/GigalogResource.php'),
        ], ['gigalog', 'gigalog-resource']);
    }

    protected function getMigrationFileName(string $migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(fn ($path) => $filesystem->glob($path.'*_'.$migrationFileName))
            ->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}
