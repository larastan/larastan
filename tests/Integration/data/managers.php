<?php

namespace Managers;

use Illuminate\Support\Manager;


function testManagerReturnsContract(DummyManagerReturnsContract $manager): void {
    $manager->type();
    $manager->publicMethodNotInContract();
}

function testManagerReturnsImplementation(DummyManagerReturnsImpl $manager): void {
    $manager->type();
    $manager->publicMethodNotInContract();
}


class DummyDriver implements DummyDriverContract
{
    public function type(): string {
        return 'dummy';
    }

    public function publicMethodNotInContract(): string {
        return 'Hello World';
    }
}

interface DummyDriverContract
{
    public function type(): string;
}

class DummyManagerReturnsContract extends Manager
{
    public function getDefaultDriver(): string {
        return 'dummy';
    }

    public function createDummyDriver(): DummyDriverContract {
        return app(DummyDriver::class);
    }
}

class DummyManagerReturnsImpl extends Manager
{
    public function getDefaultDriver(): string {
        return 'dummy';
    }

    public function createDummyDriver(): DummyDriver {
        return app(DummyDriver::class);
    }
}
