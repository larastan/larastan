<?php

namespace DynamicWithMethods;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Mail\Mailable;
use Illuminate\View\View;

use function PHPStan\Testing\assertType;

class WelcomeMail extends Mailable
{
}

function test(Mailable $mailable, WelcomeMail $welcomeMail, View $view, ViewContract $viewContract): void
{
    assertType(RedirectResponse::class, redirect()->back()->withSuccess(true));
    assertType(RedirectResponse::class, redirect()->back()->withCookie('foo'));

    assertType(View::class, $view->withFoo('bar'));
    assertType(View::class, view('foo')->withFoo('bar')->withBar('baz'));
    assertType(ViewContract::class, $viewContract->withFoo('bar'));

    assertType(Mailable::class, $mailable->withFoo('bar'));
    assertType(WelcomeMail::class, $welcomeMail->withFoo('bar'));

    // Macros take precedence over the dynamic with* methods.
    assertType('Illuminate\Mail\Mailable', $mailable->mailableMacro());
    assertType('DynamicWithMethods\WelcomeMail', $welcomeMail->mailableMacro());
    assertType('Illuminate\View\View', $view->viewMacro());
    assertType('Illuminate\Http\RedirectResponse', redirect()->back()->redirectResponseMacro());

    assertType('*ERROR*', $mailable->doesNotExist());
    assertType('*ERROR*', $view->WithFoo('bar'));
}
