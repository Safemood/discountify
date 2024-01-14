<?php

namespace Safemood\Discountify\Commands;

use Illuminate\Console\Command;

class DiscountifyCommand extends Command
{
    public $signature = 'discountify';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
