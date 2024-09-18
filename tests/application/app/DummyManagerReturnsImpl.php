<?php

namespace App;

use Illuminate\Support\Manager;

/**
 * @mixin DummyDriverContract
 */
class DummyManagerReturnsImpl extends Manager
{
    public function getDefaultDriver(): string {
        return 'dummy';
    }

    public function createDummyDriver(): DummyDriver {
        return app(DummyDriver::class);
    }
}
