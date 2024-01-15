<?php

namespace Safemood\Discountify;

use Illuminate\Contracts\Foundation\Application;
use Safemood\Discountify\Commands\DiscountifyCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DiscountifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('discountify')
            ->hasConfigFile()
            ->hasCommand(DiscountifyCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(ConditionManager::class, function () {
            return new ConditionManager();
        });

        $this->app->singleton(Discountify::class, function (Application $app) {
            return new Discountify($app->make(ConditionManager::class));
        });
    }
}
