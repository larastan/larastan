<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Application;
use Larastan\Larastan\Rules\RouteNameExistenceRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use ReflectionClass;
use Larastan\Larastan\Internal\ConsoleApplicationResolver;
use function str_replace;
use function version_compare;

/** @extends RuleTestCase<RouteNameExistenceRule> */
class RouteNameExistenceRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new RouteNameExistenceRule();
    }

    /** @test */
    public function itDoesFailForNonExistingRoutes(): void
    {
        app('router')->get('/foo123', fn() => 'foo')->name('foo');
        app('router')->getRoutes()->refreshNameLookups();
        $this->analyse([__DIR__ . '/data/route-calls.php'], [
            ["Route name: 'bar' does not exist", 9],
            ["Route name: 'bar' does not exist", 10],
        ]);
    }
}
