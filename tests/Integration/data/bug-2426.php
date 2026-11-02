<?php

namespace Bug2426;

use Illuminate\Support\Facades\Http;

function test(): void
{
    Http::post('localhost', null);
    Http::post('localhost', (object) []);
    Http::patch('localhost', null);
    Http::put('localhost', null);
    Http::delete('localhost', null);
}
