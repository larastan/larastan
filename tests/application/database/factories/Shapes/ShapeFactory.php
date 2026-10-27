<?php

declare(strict_types=1);

namespace Database\Factories\Shapes;

use App\Shapes\Shape;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shape> */
class ShapeFactory extends Factory
{
    protected $model = Shape::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}
