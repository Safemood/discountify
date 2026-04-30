<?php

declare(strict_types=1);

namespace Safemood\Discountify\Commands;

use Illuminate\Console\Command;
use Safemood\Discountify\Models\Setting;

class SettingCommand extends Command
{
    protected $signature = 'discountify:setting
                            {key : Setting key}
                            {value? : Setting value}
                            {--force : Overwrite existing setting}';

    protected $description = 'Create or retrieve a Discountify setting';

    public function handle(): int
    {
        $key = (string) $this->argument('key');
        $value = $this->argument('value');

        if ($value === null) {
            $existing = Setting::query()->where('key', $key)->first();

            if (! $existing) {
                $this->info("Setting [{$key}] not found.");

                return self::SUCCESS;
            }

            $this->line($existing->value);

            return self::SUCCESS;
        }

        $setting = Setting::query()->where('key', $key)->first();
        if ($setting && ! $this->option('force')) {
            $this->error("Setting [{$key}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $attributes = ['value' => (string) $value];

        if ($setting) {
            $setting->update($attributes);
        } else {
            Setting::create(array_merge(['key' => $key], $attributes));
        }

        $this->info("Setting [{$key}] saved successfully.");

        return self::SUCCESS;
    }
}
