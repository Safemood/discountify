<?php

declare(strict_types=1);

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

    public function packageRegistered(): void
    {

        $this->app->singleton(ConditionManager::class, function () {
            $conditionManager = new ConditionManager;

            $conditionManager->discover(
                config('discountify.condition_namespace'),
                config('discountify.condition_path')
            );

            return $conditionManager;
        });

        $this->app->singleton(CouponManager::class, function () {
            return new CouponManager;
        });

        $this->app->singleton(Discountify::class, function (Application $app) {
            return new Discountify(
                $app->make(ConditionManager::class),
                $app->make(CouponManager::class)
            );
        });
    }
}
