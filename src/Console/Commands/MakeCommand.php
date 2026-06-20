<?php

declare(strict_types=1);

namespace ChIS\Modules\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class MakeCommand extends Command
{
    protected $signature = 'module:make {module}';
    protected $description = 'Create a new module';

    public function __construct(private readonly Filesystem $fs)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $parts = explode('.', str_replace(['/', '\\', '.'], '.', $this->argument('module')));
        $model = Str::studly(Str::singular(end($parts)));
        $module = implode('\\Modules\\', $parts);
        $stubs = $this->fs->exists($p = resource_path('modules/stubs')) ? $p : __DIR__ . '/../../stubs';
        $params = $this->params($module, $model);
        collect($this->fs->allFiles($stubs))->each(function ($file) use ($params, $module) {
            $content = str_replace(array_map(fn($k) => '{' . $k . '}', array_keys($params)), $params, $this->fs->get($file));
            $lines = explode("\n", $content);
            $config = json_decode(trim(array_shift($lines)), true);
            if (!$path = $config['path'] ?? null) {
                $this->error("Missing path in {$file->getFilename()}");
                return;
            }
            $target = app_path("Modules/{$module}/{$path}");
            $target = str_replace('\\', '/', $target);
            // if (!$this->fs->exists($target)) {
            $this->fs->ensureDirectoryExists(dirname($target));
            $this->fs->put($target, implode("\n", $lines));
            $this->line("Created: {$target}");
            // }
        });
        return 0;
    }

    private function params(string $module, string $model): array
    {
        $domain = str_replace('.modules.', '.', strtolower(str_replace(['/', '\\'], '.', $module)));
        $object = Str::snake($model);
        $objects = Str::plural($object);
        return [
            'NAMESPACE' => $module,
            'DOMAIN'    => $domain,
            'MODEL'     => $model,
            'OBJECT'    => $object,
            'OBJECTS'   => $objects,
            'TABLE'     => str_replace('.', '_', $domain),
            'ROUTE'     => str_replace('.', '/', $domain),
            'DATETIME'  => Carbon::now()->format('Y_m_d_His_u'),
            'LANG'      => config('modules.lang', 'ru'),
        ];
    }
}
