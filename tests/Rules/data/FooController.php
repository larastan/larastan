<?php

namespace Tests\Rules\Data;

use Illuminate\Contracts\View\View;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Factory;

class FooController
{
    public function index()
    {
        return view('index');
    }

    public function existing(): View
    {
        return view('users.index');
    }

    public function existingNested(): View
    {
        return view('emails.orders.shipped');
    }

    public function notExisting(): View
    {
        return view('foo');
    }
}

class FooEmail extends Mailable
{
    public function build(): self
    {
        return $this->markdown('emails.markdown');
    }

    public function bar(): self
    {
        return $this->view('emails.view');
    }
}

function viewHelper(): View
{
    return view()->make('view-helper-make');
}

function viewFactory(Factory $factory): View
{
    return $factory->make('view-factory-make');
}

function viewStaticMake(): View
{
    return \Illuminate\Support\Facades\View::make('view-static-make');
}

function routeView(): void
{
    Route::view('/welcome', 'route-view');
}
