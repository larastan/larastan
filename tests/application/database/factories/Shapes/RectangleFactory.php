<?php

declare(strict_types=1);

namespace Database\Factories\Shapes;

use App\Shapes\Rectangle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rectangle> */
class RectangleFactory extends Factory
{
    protected $model = Rectangle::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}
