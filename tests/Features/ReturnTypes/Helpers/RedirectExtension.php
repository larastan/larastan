<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

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
