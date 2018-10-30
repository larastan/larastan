<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\View\View;
use Illuminate\Contracts\View\Factory;

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
