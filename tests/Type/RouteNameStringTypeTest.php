<?php

namespace Type;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use PHPStan\Rules\Functions\CallToFunctionParametersRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Tests the 'route-string-name' actually works as expected.
 *
 * This is done by example using the commonly used {@link route()} helper.
 * It is realized as a {@link RuleTestCase} for the
 * {@link CallToFunctionParametersRule}, since it validates types, and we can
 * properly set up our named routes as desired.
 */
class RouteNameStringTypeTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(CallToFunctionParametersRule::class);
    }

    public function testWithFile(): void
    {
        /** @var Router $router */
        $router = Application::getInstance()->get('router');
        $router->get('existing')->name('existing');
        $router->get('also-existing')->name('also-existing');
        $router->get('also-another-existing')->name('also-another-existing');

        $router->getRoutes()->refreshNameLookups();

        $this->analyse([
            __DIR__.'/data/route-access.php',
        ], [
            ['Parameter #1 $name of function route expects route-name-string, string given.', 4, "Route 'existingg' does not exist. Did you mean 'existing'?"],
            ['Parameter #1 $name of function route expects route-name-string, string given.', 5, "Route 'abcdefghjkl' does not exist."],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../phpstan-tests.neon'];
    }
}
