<?php

namespace Tests\Rules\Data;

use Illuminate\Support\Facades\Route;

Route::get('/foo123', fn() => 'foo')->name('foo');

route('foo');
to_route('foo');
route('bar');
to_route('bar');

