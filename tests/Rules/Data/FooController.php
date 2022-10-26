<?php

namespace Tests\Rules\Data;

use Illuminate\Contracts\View\View;
use Illuminate\Mail\Mailable;

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
        return $this->markdown('emails.orders.shipped');
    }

    public function foo(): self
    {
        return $this->markdown('home');
    }
}
