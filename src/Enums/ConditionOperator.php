<?php

declare(strict_types=1);

namespace Safemood\Discountify\Enums;

/**
 * Comparison operators available for DB-driven conditions.
 * Non-developers pick these from a dropdown in the management UI.
 */
enum ConditionOperator: string
{
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case Equal = 'eq';
    case NotEqual = 'neq';
    case In = 'in';
    case NotIn = 'nin';

    public function label(): string
    {
        return match ($this) {
            self::GreaterThan => 'Greater than',
            self::GreaterThanOrEqual => 'Greater than or equal',
            self::LessThan => 'Less than',
            self::LessThanOrEqual => 'Less than or equal',
            self::Equal => 'Equal to',
            self::NotEqual => 'Not equal to',
            self::In => 'In list',
            self::NotIn => 'Not in list',
        };
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return match ($this) {
            self::GreaterThan => $actual > $expected,
            self::GreaterThanOrEqual => $actual >= $expected,
            self::LessThan => $actual < $expected,
            self::LessThanOrEqual => $actual <= $expected,
            self::Equal => $actual == $expected,
            self::NotEqual => $actual != $expected,
            self::In => in_array($actual, (array) $expected, strict: false),
            self::NotIn => ! in_array($actual, (array) $expected, strict: false),
        };
    }
}
