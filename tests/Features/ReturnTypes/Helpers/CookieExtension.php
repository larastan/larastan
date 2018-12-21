<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Cookie\CookieJar;
use Symfony\Component\HttpFoundation\Cookie;

class CookieExtension
{
    public function testCookie(): Cookie
    {
        return cookie('test');
    }

    public function testCookieJar(): CookieJar
    {
        return cookie();
    }
}
