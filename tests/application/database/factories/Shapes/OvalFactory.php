<?php

declare(strict_types=1);

namespace Database\Factories\Shapes;

use App\Shapes\Oval;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Oval> */
class OvalFactory extends Factory
{
    protected $model = Oval::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}
