<?php

namespace App;

use Illuminate\Support\Manager;

/**
 * @mixin DummyDriverContract
 */
class DummyManagerReturnsContract extends Manager
{
    public function getDefaultDriver(): string {
        return 'dummy';
    }

    public function createDummyDriver(): DummyDriverContract {
        return app(DummyDriver::class);
    }
}
