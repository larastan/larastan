<?php

declare(strict_types=1);

namespace DatabaseTransaction;

use Illuminate\Support\Facades\DB;

use function PHPStan\Testing\assertType;

function test(float $float): void
{
    assertType('1', DB::transaction(fn () => 1));
    assertType('\'lorem\'', DB::transaction(fn () => 'lorem'));
    assertType('8.1', DB::transaction(fn () => 8.1));
    assertType('true', DB::transaction(fn () => true));
    assertType('null', DB::transaction(function () {
        echo 'ipsum';
    }));
    assertType('float|null', DB::transaction(function () use ($float) {
        $number = rand();

        if ($number % 2 === 0) {
            return null;
        }

        return $float;
    }));
}
