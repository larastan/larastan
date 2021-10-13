<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Exception;
use function PHPStan\Testing\assertType;

class RescueStub
{
    public function testRescueWithNullDefault(): void
    {
        $rescued = rescue(function () {
            if (mt_rand(0, 1)) {
                throw new Exception();
            }

            return 'ok';
        });

        assertType('string|null', $rescued);
    }

    public function testRescueWithScalarDefault(): void
    {
        $rescued = rescue(function () {
            if (mt_rand(0, 1)) {
                throw new Exception();
            }

            return 'ok';
        }, 'failed');

        assertType('string', $rescued);
    }

    public function testRescueWithClosureDefault(): void
    {
        $rescued = rescue(function () {
            if (mt_rand(0, 1)) {
                throw new Exception();
            }

            return 'ok';
        }, function () {
            return 0;
        });

        assertType('int|string', $rescued);
    }

    public function testRetryWithoutReporting(): void
    {
        $rescued = rescue(function () {
            if (mt_rand(0, 1)) {
                throw new Exception();
            }

            return 'ok';
        }, 'failed', false);

        assertType('string', $rescued);
    }
}
