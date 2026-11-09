<?php

declare(strict_types=1);

namespace Database\Factories\Shapes;

use App\Shapes\Rhombus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rhombus> */
class RhombusFactory extends Factory
{
    protected $model = Rhombus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}
