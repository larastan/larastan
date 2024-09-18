<?php

namespace Managers;

use App\DummyManagerReturnsContract;
use App\DummyManagerReturnsImpl;

function testManagerReturnsContract(DummyManagerReturnsContract $manager): void {
    $manager->type();
    $manager->publicMethodNotInContract();
}

function testManagerReturnsImplementation(DummyManagerReturnsImpl $manager): void {
    $manager->type();
    $manager->publicMethodNotInContract();
}
