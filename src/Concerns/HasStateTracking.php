<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

trait HasStateTracking
{
    protected string $stateFilePath;

    public function setStateFilePath(string $path): self
    {
        $this->stateFilePath = $path;
        $this->ensureStateFileExists();
        $this->loadState();

        return $this;
    }

    protected function ensureStateFileExists(): void
    {

        $directory = dirname($this->stateFilePath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::exists($this->stateFilePath)) {
            File::put($this->stateFilePath, json_encode([]));
        }
    }

    /**
     * Load the state from the file, converting date strings back to Carbon instances.
     */
    protected function loadState(): void
    {
        if (File::exists($this->stateFilePath)) {
            $this->coupons = json_decode(File::get($this->stateFilePath), true) ?: [];

            // Convert ISO date strings to Carbon instances during load
            foreach ($this->coupons as &$coupon) {
                $coupon = $this->convertDatesToCarbon($coupon);
            }
        }
    }

    /**
     * Save the state to the file, converting Carbon dates to ISO strings.
     */
    protected function saveState(): void
    {
        // Convert Carbon date instances to ISO strings before saving
        $couponsToSave = array_map([$this, 'convertDatesToIso'], $this->coupons);

        File::put($this->stateFilePath, json_encode($couponsToSave));
    }

    /**
     * Convert ISO date strings to Carbon instances for the given coupon.
     *
     * @param  array  $coupon  The coupon to convert.
     * @return array The coupon with Carbon instances for dates.
     */
    protected function convertDatesToCarbon(array $coupon): array
    {
        if (isset($coupon['startDate']) && is_string($coupon['startDate'])) {
            $coupon['startDate'] = Carbon::parse($coupon['startDate']);
        }

        if (isset($coupon['endDate']) && is_string($coupon['endDate'])) {
            $coupon['endDate'] = Carbon::parse($coupon['endDate']);
        }

        return $coupon;
    }

    /**
     * Convert Carbon instances to ISO strings for the given coupon.
     *
     * @param  array  $coupon  The coupon to convert.
     * @return array The coupon with ISO date strings.
     */
    protected function convertDatesToIso(array $coupon): array
    {
        if (isset($coupon['startDate']) && $coupon['startDate'] instanceof \Carbon\Carbon) {
            $coupon['startDate'] = $coupon['startDate']->toISOString();
        }

        if (isset($coupon['endDate']) && $coupon['endDate'] instanceof \Carbon\Carbon) {
            $coupon['endDate'] = $coupon['endDate']->toISOString();
        }

        return $coupon;
    }
}
