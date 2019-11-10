<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class ViewExtension
{
    public function testView(): View
    {
        return view('foo');
    }

    public function testViewFactory(): Factory
    {
        return view();
    }
}
