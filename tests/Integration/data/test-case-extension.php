<?php

use App\User;
use Illuminate\Foundation\Application;

class TestCaseExtension
{
    public function testMockMethod(): void
    {
        (new TestTestCase('foo'))->testMockMethod();
    }

    public function testPartialMockMethod(): void
    {
        (new TestTestCase('foo'))->testPartialMockMethod();
    }

    public function testSpyMethod(): void
    {
        (new TestTestCase('foo'))->testSpyMethod();
    }
}

class TestTestCase extends \Illuminate\Foundation\Testing\TestCase
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
