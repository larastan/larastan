<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

class InvalidRouteActions
{
    public function testArray(): void
    {
        Route::get('/hello', ['\Not\A\Controller', 'index'])
            ->name('hello');

        Route::post('/bye', [UserController::class, 'notAMethod']);

        Route::options('/magical', [FooController::class, 'magic']);
    }

    public function testString(): void
    {
        Route::patch('/some/route', 'non_existing_class@non_existing_method');

        Route::match(['put', 'patch'], '/hello', '\App\Http\Controllers\UserController@non_existing_method');
    }

    public function testUses(): void
    {
        Route::patch('/patch-something', [
            'uses' => '',
            'name' => 'uses-empty-string',
        ]);

        Route::any('/any', [
            'uses' => '\App\Http\Controllers\UserController@nonExisting',
        ]);
    }

    public function testGroup(): void
    {
        Route::group(['middleware' => 'can:destroy cookies'], function (): void {
            Route::delete('/cookie/{cookie}', [UserController::class, 'typo']);
        });
    }
}

class FooController
{
    /**
     * @param string $name
     * @param array<mixed> $arguments
     */
    public function __call($name, $arguments): void
    {
    }
}
