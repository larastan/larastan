<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Foundation\Application;

class TestCaseMockeryExtension
{
    public function testGetMethod(): void
    {
        (new TestMakesRequestTestCase())->testGet();
    }
}

class TestMakesRequestTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function testGet(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function createApplication()
    {
        return new Application('');
    }
}
