<?php

declare(strict_types=1);
use Safemood\Discountify\Models\Condition;

describe('discountify:condition artisan command', function (): void {

    beforeEach(function (): void {
        // Point condition path to a temp dir inside the test run
        $this->conditionPath = sys_get_temp_dir().'/discountify_test_conditions_'.uniqid();
        mkdir($this->conditionPath, 0755, true);

        config([
            'discountify.condition_path' => $this->conditionPath,
            'discountify.condition_namespace' => 'App\\Conditions',
        ]);
    });

    afterEach(function (): void {
        // Clean up generated files
        array_map('unlink', glob("{$this->conditionPath}/*.php") ?: []);
        @rmdir($this->conditionPath);
    });

    it('creates a database condition by default', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'BigCartDiscount',
            '--field' => 'count',
            '--operator' => 'gte',
            '--value' => '5',
            '--discount' => '10',
        ])->assertExitCode(0);

        $condition = Condition::query()
            ->where('slug', 'big_cart_discount')
            ->first();

        expect($condition)->not->toBeNull();
        expect($condition->discount)->toBe(10.0);
        expect($condition->operator->value)->toBe('gte');
        expect($condition->field)->toBe('count');
    });

    it('creates a condition class file when using --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'BigCartDiscount',
            '--class' => true,
        ])->assertExitCode(0);

        expect(file_exists("{$this->conditionPath}/BigCartDiscount.php"))->toBeTrue();
    });

    it('file contains the correct class name when using --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'SummerDiscount',
            '--class' => true,
        ]);

        $contents = file_get_contents("{$this->conditionPath}/SummerDiscount.php");
        expect($contents)->toContain('class SummerDiscount');
    });

    it('file contains the correct namespace when using --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'MyDiscount',
            '--class' => true,
        ]);

        $contents = file_get_contents("{$this->conditionPath}/MyDiscount.php");
        expect($contents)->toContain('namespace App\\Conditions');
    });

    it('uses snake_case slug by default when using --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'BigCartDiscount',
            '--class' => true,
        ]);

        $contents = file_get_contents("{$this->conditionPath}/BigCartDiscount.php");
        expect($contents)->toContain("'big_cart_discount'");
    });

    it('uses --slug option when provided with --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'BigCartDiscount',
            '--class' => true,
            '--slug' => 'custom_slug',
        ]);

        $contents = file_get_contents("{$this->conditionPath}/BigCartDiscount.php");
        expect($contents)->toContain("'custom_slug'");
    });

    it('uses --discount option with --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'TenOff',
            '--class' => true,
            '--discount' => '10',
        ]);

        $contents = file_get_contents("{$this->conditionPath}/TenOff.php");
        expect($contents)->toContain('10');
    });

    it('uses --type option with --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'FixedOff',
            '--class' => true,
            '--type' => 'fixed',
        ]);

        $contents = file_get_contents("{$this->conditionPath}/FixedOff.php");
        expect($contents)->toContain("'fixed'");
    });

    it('uses --priority option with --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'HighPriority',
            '--class' => true,
            '--priority' => '99',
        ]);

        $contents = file_get_contents("{$this->conditionPath}/HighPriority.php");
        expect($contents)->toContain('99');
    });

    it('implements ConditionInterface when using --class', function (): void {
        $this->artisan('discountify:condition', [
            'name' => 'ChecksInterface',
            '--class' => true,
        ]);

        $contents = file_get_contents("{$this->conditionPath}/ChecksInterface.php");
        expect($contents)->toContain('implements ConditionInterface');
    });

    it('fails without --force when class file already exists', function (): void {
        $this->artisan('discountify:condition', ['name' => 'Duplicate', '--class' => true])->assertExitCode(0);
        $this->artisan('discountify:condition', ['name' => 'Duplicate', '--class' => true])->assertExitCode(1);
    });

    it('overwrites class file with --force', function (): void {
        $this->artisan('discountify:condition', ['name' => 'Overwrite', '--class' => true])->assertExitCode(0);
        $this->artisan('discountify:condition', ['name' => 'Overwrite', '--class' => true, '--force' => true])->assertExitCode(0);
    });

    it('creates the condition directory if it does not exist when using --class', function (): void {
        $nested = $this->conditionPath.'/nested/deep';
        config(['discountify.condition_path' => $nested]);

        $this->artisan('discountify:condition', ['name' => 'DeepDir', '--class' => true])->assertExitCode(0);

        expect(is_dir($nested))->toBeTrue();

        // cleanup
        @unlink("{$nested}/DeepDir.php");
        @rmdir($nested);
        @rmdir(dirname($nested));
    });

    it('file contains declare(strict_types=1) when using --class', function (): void {
        $this->artisan('discountify:condition', ['name' => 'StrictTypes', '--class' => true]);

        $contents = file_get_contents("{$this->conditionPath}/StrictTypes.php");
        expect($contents)->toContain('declare(strict_types=1)');
    });

    it('file has an __invoke method returning bool when using --class', function (): void {
        $this->artisan('discountify:condition', ['name' => 'HasInvoke', '--class' => true]);

        $contents = file_get_contents("{$this->conditionPath}/HasInvoke.php");
        expect($contents)->toContain('public function __invoke(array $items): bool');
    });

});
