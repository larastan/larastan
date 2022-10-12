<?php

namespace Facades;

use Illuminate\Support\Facades\Request;
use function PHPStan\Testing\assertType;

assertType('Illuminate\Http\Request', Request::instance());
