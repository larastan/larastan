<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class NoInvalidRouteActionTest extends RulesTest
{
    public function testNoFalsePositives(): void
    {
        $errors = $this->findErrors(__DIR__.'/Data/ValidRouteActions.php');
        $this->assertEquals([], $errors, 'The rule should not result in any errors for this data set.');
    }

    public function testNoFalseNegatives(): void
    {
        $errors = $this->findErrorsByLine(__DIR__.'/Data/InvalidRouteActions.php');

        $this->assertEquals([
            14 => 'Detected non-existing class \'\\Not\A\Controller\' during route registration.',
            17 => 'Detected non-existing method \'notAMethod\' on class \'\\App\\Http\\Controllers\\UserController\' during route registration.',
            22 => 'Detected non-existing class \'non_existing_class\' during route registration.',
            24 => 'Detected non-existing method \'non_existing_method\' on class \'\\App\\Http\\Controllers\\UserController\' during route registration.',
            29 => 'Detected non-existing class \'\' during route registration.',
            34 => 'Detected non-existing method \'nonExisting\' on class \'\\App\\Http\\Controllers\\UserController\' during route registration.',
            42 => 'Detected non-existing method \'typo\' on class \'\\App\\Http\\Controllers\\UserController\' during route registration.',
        ], $errors);
    }
}
