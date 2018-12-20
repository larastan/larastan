<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;

class RedirectExtension
{
    public function testRedirect(): RedirectResponse
    {
        return redirect('/');
    }

    public function testRedirector(): Redirector
    {
        return redirect();
    }
}
