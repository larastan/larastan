<?php declare(strict_types=1);

namespace Tests\Features\Rules;

use App\User;

class EloquentWhereParametersRuleTest
{
    /** @test */
    public function it_allows_declared_properties(): void
    {
        User::where(['name' => 'hi'])->get();
    }

    /** @test */
    public function it_checks_types(): void
    {
        User::where(['name' => new \stdClass()])->get();
    }

    /** @test */
    public function it_does_not_allow_undeclared_properties(): void
    {
        User::where(['plz_fail' => true])->get();
    }
}
