<?php

namespace Safemood\Discountify;

use Illuminate\Contracts\Foundation\Application;
use Safemood\Discountify\Commands\ConditionMakeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DiscountifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('discountify')
            ->hasConfigFile()
            ->hasCommand(ConditionMakeCommand::class);
    }

    public function packageRegistered()
    {

        $this->app->singleton(ConditionManager::class, function ($app) {
            $conditionManager = new ConditionManager();

            $conditionManager->discover(
                config('discountify.condition_namespace'),
                config('discountify.condition_path')
            );

            return $conditionManager;
        });

        $this->app->singleton(Discountify::class, function (Application $app) {
            return new Discountify($app->make(ConditionManager::class));
        });
    }
}
