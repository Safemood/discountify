<?php

namespace Safemood\Discountify;

use Safemood\Discountify\Commands\DiscountifyCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DiscountifyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('discountify')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_discountify_table')
            ->hasCommand(DiscountifyCommand::class);
    }
}
