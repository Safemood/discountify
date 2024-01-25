<?php

namespace Safemood\Discountify\Exceptions;

use InvalidArgumentException;

class DuplicateSlugException extends InvalidArgumentException
{
    public function __construct(string $slug)
    {
        parent::__construct("Duplicate slug found: '{$slug}'. Each discount must have a unique identifier.");
    }
}
