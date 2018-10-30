<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\View\View;
use Illuminate\Contracts\View\Factory;

class ViewExtension
{
    public function testSimpleView(): View
    {
        return view('test');
    }

    public function testFactory(): Factory
    {
        return view();
    }
}
