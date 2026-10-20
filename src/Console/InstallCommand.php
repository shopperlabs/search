<?php

declare(strict_types=1);

namespace Shopper\Search\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'shopper:search:install')]
final class InstallCommand extends Command
{
    protected $signature = 'shopper:search:install';

    protected $description = 'Install the search addon and register it in the application';

    public function handle(): int
    {
        intro('Installing Shopper Search');

        $this->registerAddon();

        note('Set SCOUT_DRIVER=typesense and your TYPESENSE_* credentials in .env, then run:');
        note('php artisan scout:import "'.config('shopper.models.product').'"');

        outro('Shopper Search is installed.');

        return self::SUCCESS;
    }

    private function task(string $title, Closure $callback): void
    {
        spin(callback: $callback, message: $title);

        $width = 52;
        $dots = str_repeat('.', max(1, $width - mb_strlen($title)));

        $this->output->writeln("  <fg=#94A3B8>{$title}</> <fg=#334155>{$dots}</> <fg=#22C55E>✓</>");
    }

    private function registerAddon(): void
    {
        $path = app_path('Providers/AppServiceProvider.php');

        if (! File::exists($path)) {
            $this->manualRegistrationNotice();

            return;
        }

        $contents = File::get($path);

        if (str_contains($contents, 'SearchAddon')) {
            note('SearchAddon is already registered in AppServiceProvider.');

            return;
        }

        $updated = $this->addImports($contents);
        $updated = $this->addAddonRegistration($updated);

        if ($updated === null) {
            $this->manualRegistrationNotice();

            return;
        }

        $this->task('Registering SearchAddon in AppServiceProvider', function () use ($path, $updated): void {
            File::put($path, $updated);
        });
    }

    private function addImports(string $contents): string
    {
        $imports = '';

        if (! str_contains($contents, 'use Shopper\Facades\Shopper;')) {
            $imports .= "use Shopper\\Facades\\Shopper;\n";
        }

        if (! str_contains($contents, 'use Shopper\Search\SearchAddon;')) {
            $imports .= "use Shopper\\Search\\SearchAddon;\n";
        }

        if ($imports === '') {
            return $contents;
        }

        return (string) preg_replace('/^(namespace [^;]+;\n\n?)/m', '$1'.$imports, $contents, 1);
    }

    private function addAddonRegistration(string $contents): ?string
    {
        if (str_contains($contents, 'Shopper::addons([')) {
            return str_replace(
                'Shopper::addons([',
                "Shopper::addons([\n            new SearchAddon,",
                $contents,
            );
        }

        $patched = preg_replace(
            '/(public function register\(\): void\s*\{)/',
            "$1\n        Shopper::addons([\n            new SearchAddon,\n        ]);\n",
            $contents,
            1,
            $count,
        );

        return $count === 1 ? $patched : null;
    }

    private function manualRegistrationNotice(): void
    {
        warning('Could not update AppServiceProvider automatically. Register the addon manually:');
        note('Shopper::addons([new \Shopper\Search\SearchAddon]);');
    }
}
