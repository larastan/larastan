<?php

namespace Bug2431;

use Illuminate\Support\Facades\Http;

function test(): void
{
    Http::asMultipart()->post('localhost', [
        [
            'name' => 'a',
            'contents' => 'a',
        ],
    ]);

    Http::asMultipart()->post('localhost', [
        [
            'name' => 'a',
            'contents' => 'a',
        ],
        [
            'name' => 'b',
            'contents' => 'b',
        ],
    ]);
}
