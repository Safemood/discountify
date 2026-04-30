<?php

declare(strict_types=1);

use Safemood\Discountify\Exceptions\CouponException;

describe('CouponException', function (): void {

    it('noCode() produces the correct message', function (): void {
        $e = CouponException::noCode();
        expect($e->getMessage())->toBe('No coupon code has been set.')
            ->and($e)->toBeInstanceOf(CouponException::class);
    });

    it('notFound() embeds the code in the message', function (): void {
        $e = CouponException::notFound('GHOST99');
        expect($e->getMessage())->toContain('GHOST99')
            ->and($e->getMessage())->toContain('does not exist');
    });

    it('notValid() embeds the code in the message', function (): void {
        $e = CouponException::notValid('OLD50');
        expect($e->getMessage())->toContain('OLD50')
            ->and($e->getMessage())->toContain('inactive');
    });

    it('exhausted() embeds the code in the message', function (): void {
        $e = CouponException::exhausted('USED');
        expect($e->getMessage())->toContain('USED')
            ->and($e->getMessage())->toContain('maximum usage');
    });

    it('notAllowedForUser() embeds the code', function (): void {
        $e = CouponException::notAllowedForUser('VIP');
        expect($e->getMessage())->toContain('VIP')
            ->and($e->getMessage())->toContain('cannot be used by this user');
    });

    it('belowMinimumOrder() embeds code and minimum value', function (): void {
        $e = CouponException::belowMinimumOrder('BIG', 200.0);
        expect($e->getMessage())->toContain('BIG')
            ->and($e->getMessage())->toContain('200')
            ->and($e->getMessage())->toContain('minimum order value');
    });

    it('is a RuntimeException', function (): void {
        expect(CouponException::noCode())->toBeInstanceOf(RuntimeException::class);
    });

    it('is final and cannot be extended', function (): void {
        $reflection = new ReflectionClass(CouponException::class);
        expect($reflection->isFinal())->toBeTrue();
    });

});
