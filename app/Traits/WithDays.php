<?php

namespace App\Traits;

use App\Attributes\Days;
use App\Attributes\Description;
use ReflectionClassConstant;

trait WithDays
{
    public function days(): int
    {
        $reflection = new ReflectionClassConstant(self::class, $this->name);
        $attributes = $reflection->getAttributes(Days::class);

        return $attributes[0]->newInstance()->days;
    }
}
