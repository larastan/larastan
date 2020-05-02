<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\Http\Controllers\UserController;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class ValidRouteActions
{
    public function testArray(): void
    {
        Route::get('/hello', [UserController::class, 'index'])
            ->name('hello');

        Route::post('/bye', ['Tests\Rules\Data\FruitController', 'getPears'])
            ->name('bye')
            ->middleware('can:say bye');
    }

    public function testString(): void
    {
        Route::patch('/some/route', 'Tests\Rules\Data\FruitController@getBananas');

        Route::match(['put', 'patch'], '/hello', FruitController::class.'@non_existing_method');

        $controller = UserController::class;
        Route::get('/users', $controller.'@index');
    }

    public function testUses(): void
    {
        Route::patch('/patch-something', [
            'uses' => MagicalController::class.'@index',
            'name' => 'some-name',
        ]);

        Route::any('/any', [
            'uses' => 'Tests\Rules\Data\FruitController@getApples',
        ]);
    }

    public function testGroup(): void
    {
        Route::group(['middleware' => 'can:get fruit'], function (): void {
            foreach (['getApples', 'getPears'] as $method) {
                Route::get('/fruit/'.$method, [FruitController::class, $method]);
            }
        });
    }
}

/**
 * @method int getBananas()
 */
class FruitController
{
    public function getApples(): void
    {
    }

    public function getPears(): void
    {
    }
}

class MagicalController extends Controller
{
    public function index(): void
    {
    }

    /**
     * @param string $method
     * @param array<mixed> $parameters
     * @return int
     */
    public function __call($method, $parameters)
    {
        return $method === 'magic' ? 1 : 2;
    }
}
