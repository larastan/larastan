<?php

namespace App\Shapes;

use Database\Factories\Shapes\RectangleFactory;

class Rectangle extends Shape
{
    /** @param mixed $count @param mixed $state */
    public static function factory($count = null, $state = []): RectangleFactory
    {
        return RectangleFactory::new();
    }
}
