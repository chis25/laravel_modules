<?php

declare(strict_types=1);

namespace ChIS\Modules\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class MakeCommand extends Command
{
    protected $signature = 'module:make {module} {--preset=all} {--force} {--skip}';
    protected $description = 'Create a new module';

    public function __construct(private readonly Filesystem $fs)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $module     = $this->argument('module');
        $preset     = $this->option('preset');
        $force      = $this->option('force');
        $skip       = $this->option('skip');
        $replaces   = $this->makeReplaces($module);
        $files      = [];
        $has_errors = false;

        if ($replaces['{PARENT}']) {
            $this->call('module:make', ['module' => $replaces['{PARENT}'], '--preset' => config('modules.parent_preset')]);
        }

        $this->info("Make module {$replaces['{DOMAIN}']}");

        $stubs = config("modules.presets.{$preset}", array_keys(config('modules.stubs', [])));
        foreach ($stubs as $stub) {
            $src = $this->getStubFile($stub);
            if (!$src) {
                $this->error("Stub '{$stub}' not found");
                $has_errors = true;
                continue;
            }
            $dst = config("modules.stubs.{$stub}", false);
            if (!$dst) {
                $this->error("Missing path for stub '{$stub}'");
                $has_errors = true;
                continue;
            }
            $dst = app_path("Modules/{$replaces['{NAMESPACE}']}/{$dst}")
            |> (fn($s) => $this->replace($replaces, $s));
            $files[$src] = $dst;
        }

        if (!$has_errors || $skip) {
            foreach ($files as $src => $dst) {
                if ($this->fs->exists($dst) && !$force) continue;
                $content = $this->fs->get($src) |> (fn($s) => $this->replace($replaces, $s));
                $this->fs->ensureDirectoryExists(dirname($dst));
                $this->fs->put($dst, $content);
                $this->line("Created: {$dst}");
            };
        }

        return 0;
    }

    private function makeReplaces($module): array
    {
        $module = str_replace(['/', '\\'], '.', $module);
        $namespace = str_replace('.', '\\Modules\\', $module);
        $domain = strtolower($module);
        $parts = explode('.', $module);
        $model = array_pop($parts) |> Str::singular(...);
        $object = Str::snake($model);
        $objects = Str::plural($object);
        $parent = count($parts) ? implode('.', $parts) : false;
        return [
            '{NAMESPACE}' => $namespace,
            '{DOMAIN}'    => $domain,
            '{MODEL}'     => $model,
            '{OBJECT}'    => $object,
            '{OBJECTS}'   => $objects,
            '{TABLE}'     => str_replace('.', '_', $domain),
            '{ROUTE}'     => str_replace('.', '/', $domain),
            '{DATETIME}'  => Carbon::now()->format('Ymd_His_u'),
            '{LANG}'      => config('modules.lang', 'ru'),
            '{PARENT}'    => $parent,
        ];
    }

    private function replace($replaces, $subject): string
    {
        return str_replace(array_keys($replaces), $replaces, $subject);
    }

    private function getStubFile(string $stub): string|bool
    {
        $paths = [
            resource_path('modules/stubs'),
            __DIR__ . '/../../../stubs',
        ];
        foreach ($paths as $path) {
            $file = "{$path}/{$stub}.stub";
            if (file_exists($file)) {
                return $file;
            }
        }
        return false;
    }
}
