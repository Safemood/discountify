<?php

declare(strict_types=1);
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Architecture tests — Pest 4 arch() API.
 *
 * These ensure the codebase stays clean, consistent, and layered correctly
 * without running the full test suite.
 */
arch('source files use strict types')
    ->expect('Safemood\Discountify')
    ->toUseStrictTypes();

arch('enums are backed enums')
    ->expect('Safemood\Discountify\Enums')
    ->toBeStringBackedEnums();

arch('contracts are interfaces')
    ->expect('Safemood\Discountify\Contracts')
    ->toBeInterfaces();

arch('events are final classes')
    ->expect('Safemood\Discountify\Events')
    ->toBeFinalClasses();

arch('exceptions extend RuntimeException')
    ->expect('Safemood\Discountify\Exceptions')
    ->toExtend(RuntimeException::class);

arch('exceptions are final')
    ->expect('Safemood\Discountify\Exceptions')
    ->toBeFinalClasses();

arch('models extend Eloquent Model')
    ->expect('Safemood\Discountify\Models')
    ->toExtend(Model::class);

arch('support engines are final classes')
    ->expect('Safemood\Discountify\Support')
    ->toBeFinalClasses();

arch('facades extend Laravel Facade')
    ->expect('Safemood\Discountify\Facades')
    ->toExtend(Facade::class);

arch('commands extend Illuminate Command')
    ->expect('Safemood\Discountify\Commands')
    ->toExtend(Command::class);

arch('no source class uses dd or dump')
    ->expect('Safemood\Discountify')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

arch('no source class uses env() directly')
    ->expect('Safemood\Discountify')
    ->not->toUse('env');
