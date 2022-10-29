<?php

declare(strict_types=1);

namespace DatabaseTransaction;

use Illuminate\Support\Facades\DB;
use function PHPStan\Testing\assertType;

assertType('int', DB::transaction(fn () => 1));
assertType('string', DB::transaction(fn () => 'lorem'));
assertType('float', DB::transaction(fn () => 8.1));
assertType('bool', DB::transaction(fn () => true));

assertType('void', DB::transaction(function () {
    echo 'ipsum';
}));

assertType('float|null', DB::transaction(function () {
    $number = rand();

    if ($number % 2 === 0) {
        return null;
    }

    return sqrt($number);
}));
