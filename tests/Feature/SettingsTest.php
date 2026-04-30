<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Safemood\Discountify\DiscountifyServiceProvider;
use Safemood\Discountify\Facades\Discountify as DiscountifyFacade;
use Safemood\Discountify\Models\Setting;

describe('Discountify settings', function (): void {

    it('creates the discountify settings table', function (): void {
        expect(config('discountify.tables.settings'))->toBe('discountify_settings');
        expect(Schema::hasTable(config('discountify.tables.settings')))->toBeTrue();
    });

    it('stores and retrieves settings values', function (): void {
        Setting::create(['key' => 'global_discount', 'value' => '7']);
        Setting::create(['key' => 'global_tax_rate', 'value' => '9']);

        expect(Setting::getValue('global_discount'))->toBe('7');
        expect(Setting::getValue('global_tax_rate'))->toBe('9');
        expect(Setting::getValue('missing', 0))->toBe(0);
    });

    it('loads global discount and tax from the database via the provider', function (): void {
        Setting::create(['key' => 'global_discount', 'value' => '7']);
        Setting::create(['key' => 'global_tax_rate', 'value' => '9']);

        $provider = new DiscountifyServiceProvider($this->app);
        $reflection = new ReflectionMethod($provider, 'loadSettingsFromDatabase');
        $reflection->setAccessible(true);
        $reflection->invoke($provider);

        expect(config('discountify.global_discount'))->toBe(7.0);
        expect(config('discountify.global_tax_rate'))->toBe(9.0);
    });

    it('uses persisted global settings when checking out', function (): void {
        Setting::create(['key' => 'global_discount', 'value' => '5']);
        Setting::create(['key' => 'global_tax_rate', 'value' => '8']);

        $provider = new DiscountifyServiceProvider($this->app);
        $reflection = new ReflectionMethod($provider, 'loadSettingsFromDatabase');
        $reflection->setAccessible(true);
        $reflection->invoke($provider);

        $items = [
            ['price' => 100.0, 'quantity' => 1],
        ];

        $result = DiscountifyFacade::setItems($items)
            ->checkout();

        expect($result['global_discount'])->toBe(5.0);
        expect($result['tax'])->toBe(7.6);
        expect($result['total_with_tax'])->toBe(102.6);
    });

});
