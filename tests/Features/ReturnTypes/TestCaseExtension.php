<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Foundation\Application;
use PHPStan\Testing\TestCase;

class TestCaseExtension extends TestCase
{
    public function testTestCaseExtension(): void
    {
        $testCase = new TestTestCase();

        $testCase->testMock();
        $testCase->testPartialMock();
        $testCase->testSpy();
    }
}

class TestTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function testMock() {
        $mock = $this->mock(User::class);
        $mock->accounts();
    }

    public function testPartialMock() {
        $mock = $this->partialMock(User::class);
        $mock->accounts();
    }

    public function testSpy() {
        $mock = $this->spy(User::class);
        $mock->accounts();
    }

    public function createApplication()
    {
        return new Application('');
    }

}
