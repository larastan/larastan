<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use NunoMaduro\Larastan\ApplicationResolver;

class AppExtension
{
    public function testObjectType(): ApplicationResolver
    {
        return app(ApplicationResolver::class);
    }

    /**
     * @return mixed
     */
    public function testMixedTypeNoArgument()
    {
        return app();
    }

    /**
     * @return mixed
     */
    public function testMixedTypeFor()
    {
        return app('sentry');
    }
}
