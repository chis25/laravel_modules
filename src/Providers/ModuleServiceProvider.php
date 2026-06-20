<?php

declare(strict_types=1);

namespace ChIS\Modules\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    private array $modules = [];

    public function register(): void
    {
        $this->scan(app_path('Modules'));
        $this->commands([\ChIS\Modules\Console\Commands\MakeCommand::class]);
        $this->publishes([
            __DIR__ . '/../../stubs' => resource_path('modules/stubs'),
            __DIR__ . '/../Config/modules.php' => config_path('modules.php')
        ], 'modules');
    }

    private function scan(string $root): void
    {
        foreach (glob($root . '/*/module.php') as $file) {
            [$moduleDir, $namespace] = include $file;
            is_dir($moduleDir . '/Modules') && $this->scan($moduleDir . '/Modules');
            $this->modules[$moduleDir] = $namespace;
        }
    }

    public function boot(Filesystem $fs): void
    {
        foreach ($this->modules as $moduleDir => $namespace) {
            $dir = $moduleDir . '/Configs';
            is_dir($dir) && collect($fs->allFiles($dir))->each(fn($f) => $this->mergeConfigFrom($f, $namespace . '::' . $f->getBasename('.php')));
            $dir = $moduleDir . '/Helpers';
            is_dir($dir) && collect($fs->allFiles($dir))->each(fn($f) => include $f->getPathname());
            $dir = $moduleDir . '/Langs';
            is_dir($dir) && ($this->loadTranslationsFrom($dir, $namespace) || true) && $this->loadJsonTranslationsFrom($dir);
            is_dir($moduleDir . '/Migrations') && $this->loadMigrationsFrom($moduleDir . '/Migrations');
            $dir = $moduleDir . '/Routes';
            is_dir($dir) && collect($fs->allFiles($dir))->each(fn($f) => Route::middleware($f->getBasename('.php'))->group($f->getPathname()));
            is_dir($moduleDir . '/Views') && $this->loadViewsFrom($moduleDir . '/Views', $namespace);
        }
    }
}
