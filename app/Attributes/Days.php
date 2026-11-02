<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Days
{
    public function __construct(
        public int $days
    ) {}
}
