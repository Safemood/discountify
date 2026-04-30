<?php

declare(strict_types=1);

namespace Safemood\Discountify\Exceptions;

use RuntimeException;

final class CouponException extends RuntimeException
{
    public static function noCode(): static
    {
        return new self('No coupon code has been set.');
    }

    public static function notFound(string $code): static
    {
        return new static("Coupon [{$code}] does not exist.");
    }

    public static function notValid(string $code): static
    {
        return new static("Coupon [{$code}] is inactive or outside its valid date range.");
    }

    public static function exhausted(string $code): static
    {
        return new static("Coupon [{$code}] has reached its maximum usage limit.");
    }

    public static function notAllowedForUser(string $code): static
    {
        return new static("Coupon [{$code}] cannot be used by this user.");
    }

    public static function belowMinimumOrder(string $code, float $minimum): static
    {
        return new static("Coupon [{$code}] requires a minimum order value of {$minimum}.");
    }
}
