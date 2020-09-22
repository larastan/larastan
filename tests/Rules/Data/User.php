<?php

use Illuminate\Database\Eloquent\Model;

class ModelPropertyCustomMethods extends Model
{
    /**
     * @phpstan-param model-property<\App\User> $property
     * @param string $property
     */
    public function foo(string $property): void
    {
        // Do something with property
    }

    public function bar(): void
    {
        $this->foo('email'); // 'email' exists on \App\User
        $this->foo('foo'); // 'foo' does not exist on \App\User
    }
}

class ModelPropertyCustomMethodsInNormalClass
{
    /**
     * @phpstan-param model-property<\App\User> $property
     * @param string $property
     */
    public function foo(string $property): void
    {
        // Do something with property
    }

    public function bar(): void
    {
        $this->foo('email'); // 'email' exists on \App\User
        $this->foo('foo'); // 'foo' does not exist on \App\User
    }
}
