<?php

declare(strict_types=1);

namespace App\TestFiles\ReturnTypes\Helpers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
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

    public function testFail(): JsonResponse
    {
        return view('test');
    }
}
