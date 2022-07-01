<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use function PHPStan\Testing\assertType;

class AppAccessTest
{
    public function testEnvironment(): void
    {
        assertType('string', app()->environment());
        assertType('bool', app()->environment('local'));
        assertType('bool', app()->environment(['local', 'production']));
    }
}
