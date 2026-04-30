<?php

declare(strict_types=1);

namespace Safemood\Discountify;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Safemood\Discountify\Commands\CouponMakeCommand;
use Safemood\Discountify\Commands\InstallCommand;
use Safemood\Discountify\Commands\MakeConditionCommand;
use Safemood\Discountify\Commands\PromoMakeCommand;
use Safemood\Discountify\Commands\SettingCommand;
use Safemood\Discountify\Models\Setting;
use Safemood\Discountify\Support\ConditionEngine;
use Safemood\Discountify\Support\CouponEngine;
use Safemood\Discountify\Support\PromoEngine;

/**
 * Discountify v2 Service Provider — Laravel 11 / 12 / 13, PHP 8.4+
 */
class DiscountifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/discountify.php', 'discountify');

        $this->app->singleton(ConditionEngine::class, fn () => new ConditionEngine);
        $this->app->singleton(CouponEngine::class, fn () => new CouponEngine);
        $this->app->singleton(PromoEngine::class, fn () => new PromoEngine);

        $this->app->bind(Discountify::class, fn ($app) => new Discountify(
            conditionEngine: $app->make(ConditionEngine::class),
            couponEngine: $app->make(CouponEngine::class),
            promoEngine: $app->make(PromoEngine::class),
            globalDiscount: config('discountify.global_discount', 0),
            taxRate: config('discountify.global_tax_rate', 0),
        ));
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeConditionCommand::class,
                CouponMakeCommand::class,
                PromoMakeCommand::class,
                SettingCommand::class,
                InstallCommand::class,
            ]);
        }

        $this->loadSettingsFromDatabase();

        $this->callAfterResolving(ConditionEngine::class, function (ConditionEngine $engine): void {
            $engine->discover();

            if (config('discountify.database_driver', true)) {
                try {
                    $engine->loadFromDatabase();
                } catch (\Throwable) {
                    // Table may not exist before first migration run — silently skip.
                }
            }
        });
    }

    private function loadSettingsFromDatabase(): void
    {
        if (! config('discountify.database_driver', true)) {
            return;
        }

        $table = config('discountify.tables.settings', 'discountify_settings');

        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            $settings = Setting::query()->pluck('value', 'key')->all();

            if (isset($settings['global_discount'])) {
                config()->set('discountify.global_discount', (float) $settings['global_discount']);
            }

            if (isset($settings['global_tax_rate'])) {
                config()->set('discountify.global_tax_rate', (float) $settings['global_tax_rate']);
            }
        } catch (\Throwable) {
            // Database not ready yet; ignore and use config defaults.
        }
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes(
            [__DIR__.'/../config/discountify.php' => config_path('discountify.php')],
            ['discountify', 'discountify-config']
        );

        $this->publishes(
            [__DIR__.'/Database/migrations' => database_path('migrations')],
            ['discountify', 'discountify-migrations']
        );
    }
}
