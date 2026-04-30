<?php

declare(strict_types=1);

namespace Safemood\Discountify\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'discountify:install {--force : Overwrite existing Laravel Boost skill file}';

    protected $description = 'Publish Discountify config, run migrations, and add a Laravel Boost skill';

    public function handle(): int
    {
        $this->info('Installing Discountify v2...');

        $this->call('vendor:publish', ['--tag' => 'discountify-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'discountify-migrations', '--force' => true]);
        $this->call('migrate');

        $this->info('');
        $this->info('✓ Discountify v2 installed.');
        $this->info('  → php artisan discountify:condition MyCondition --discount=10');

        return self::SUCCESS;
    }
}
