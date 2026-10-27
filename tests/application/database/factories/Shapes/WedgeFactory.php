<?php

declare(strict_types=1);

namespace Database\Factories\Shapes;

use App\Shapes\Wedge;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Wedge> */
class WedgeFactory extends Factory
{
    protected $model = Wedge::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}
