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

        $testCase->testMockingMethod('mock');
        $testCase->testMockingMethod('partialMock');
        $testCase->testMockingMethod('spy');
    }
}

class TestTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function testMockingMethod($method): void
    {
        if (method_exists($this, $method)) {
            $mock = $this->{$method}(User::class);
            $mock->accounts();
        }
    }

    public function createApplication()
    {
        return new Application('');
    }

}
