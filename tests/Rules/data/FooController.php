<?php

namespace Tests\Rules\Data;

use Illuminate\Contracts\View\View;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
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

class FooMailable extends Mailable
{
    public function build(): self
    {
        return $this->markdown('emails.mailable.markdown');
    }

    public function bar(): self
    {
        return $this->view('emails.mailable.view');
    }
}

class FooMailMessage extends MailMessage
{
    public function build(): self
    {
        return $this->markdown('emails.mail-message.markdown');
    }

    public function bar(): self
    {
        return $this->view('emails.mail-message.view');
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
