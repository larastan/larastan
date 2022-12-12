<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Foundation\Application;

class TestCaseMockeryExtension
{
    public function testMockMethod(): void
    {
        (new TestMockeryTestCase())->testMockMethod();
    }

    public function testPartialMockMethod(): void
    {
        (new TestMockeryTestCase())->testPartialMockMethod();
    }

    public function testSpyMethod(): void
    {
        (new TestMockeryTestCase())->testSpyMethod();
    }
}

class TestMockeryTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function testMockMethod(): void
    {
        $mock = $this->mock(User::class);
        $mock->accounts();
    }

    public function testPartialMockMethod(): void
    {
        if (method_exists($this, 'partialMock')) {
            $mock = $this->partialMock(User::class);
            $mock->accounts();
        }
    }

    public function testSpyMethod(): void
    {
        $mock = $this->spy(User::class);
        $mock->accounts();
    }

    public function createApplication()
    {
        return new Application('');
    }
}
